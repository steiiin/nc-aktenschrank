<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Service;

use OCA\Aktenschrank\Abstraction\InboxItem;
use OCA\Aktenschrank\AppInfo\Application;
use OCA\Aktenschrank\Db\Cabinet;
use OCA\Aktenschrank\Db\CabinetMapper;
use OCA\Aktenschrank\Exceptions\ExBackend;
use OCA\Aktenschrank\Exceptions\ExBadRequest;
use OCA\Aktenschrank\Exceptions\ExResourceInUse;
use OCA\Aktenschrank\Helpers\Validation;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\Files\Folder;

class ApiService
{

  private CabinetMapper $cabinetMapper;

  private IConfig $appConfig;
  private IL10N $l;
  private IUserSession $userSession;
  private FileService $fileService;
  
  public function __construct (

    CabinetMapper $cabinetMapper,

    IConfig $appConfig,
    IL10N $l,
    IUserSession $userSession,
    FileService $fileService

  ) {

    $this->cabinetMapper = $cabinetMapper;

    $this->appConfig = $appConfig;
    $this->l = $l;
    $this->userSession = $userSession;
    $this->fileService = $fileService;

    $this->CABINET_NAME_INBOX = $this->l->t("Inbox");
    $this->CABINET_NAME_ARCHIVE = ".Archive";

  }

  #region Settings

  #region UserInfo

  public const SETTING_LOCALIZATION = "localization";
  public const SETTING_LOCALIZATION_TIMEZONE = "timezone";
  public const SETTING_LOCALIZATION_LANGUAGE = "language";

  /**
   * This method retrieves the user's preferred localization settings.
   *
   * @return array Key/Value array of following format:
   * {
   *   @type array $SETTING_LOCALIZATION {
   *     @type string $SETTING_LOCALIZATION_TIMEZONE Preferred timezone (e.g. "Europe/Paris").
   *     @type string $SETTING_LOCALIZATION_LANGUAGE Preferred language (e.g. "fr").
   *   }
   * }
   * 
   */
  public function getLocalizationSettings(): Array 
  {

    // get current user, if no user active resume with default values
    $userUID = $this->userSession->getUser();
		if ($userUID !== null) { $userUID = $userUID->getUID(); }
		
    // get timezone
		$defaultTimeZone = date_default_timezone_get();
		$userTimezone = $this->appConfig->getUserValue($userUID, "core", "timezone", $defaultTimeZone);

    // get language preference
		$userLanguage = $this->appConfig->getUserValue($userUID, "core", "lang", null);

    // return
    return [ self::SETTING_LOCALIZATION => [
      self::SETTING_LOCALIZATION_TIMEZONE => $userTimezone,
      self::SETTING_LOCALIZATION_LANGUAGE => $userLanguage
    ]];

  }

  #endregion
  #region UserConfig

  /**
   * This method reads a value with the specified key from NC system configuration.
   *
   * @param string $key Key for desired setting.
   * @return string|null Value of desired setting, NULL if not found or error occured.
   * 
   */
  private function getSystemConfig(string $key): mixed
  {
    return $this->appConfig->getSystemValue($key, null);
  }

  /**
   * This method reads a value with the specified key for the current active user from the NC configuration store.
   *
   * @param string $key Key for desired setting.
   * @return string|null Value of desired setting, NULL if not found or error occured.
   * 
   */
  private function getUserConfig(string $key): ?string 
  {
    $user = $this->userSession->getUser();
    if ($user === null) { return null; }
    return $this->appConfig->getUserValue($user->getUID(), Application::APP_ID, $key, null);
  }

  /**
   * This method saves a specified value in the NC configuration store.
   *
   * @param string $key   Key to find desired setting later again.
   * @param string $value Value to save.
   * @return void
   * 
   * @throws ExBackend    If no active user found, or NC fails to save the value
   * 
   */
  private function setUserConfig(string $key, mixed $value)
  {

    // check value
    if (!is_string($value) && !is_int($value) && !is_bool($value))
    {
      throw new ExBackend("set invalid user config", [ "value" => $value ]);
    }

    // get user
    $user = $this->userSession->getUser();
    if ($user === null) { throw new ExBackend("no active user"); }

    try 
    {
      $this->appConfig->setUserValue($user->getUID(), Application::APP_ID, $key, $value);
    }
    catch (\Exception $ex)
    {
      throw new ExBackend("failed setting user config", [ "key" => $key, "value" => $value ], $ex);
    }
  }

  #endregion

  #region Cabinet

  public const SETTING_CABINET = "cabinet";

  public const SETTING_CABINET_PATH = "path";
  public const SETTING_CABINET_IsREADY = "isReady";
  public const SETTING_CABINET_IsCONFIGURED = "isConfigured";
  public const SETTING_CABINET_IsEXISTING = "isExisting";
  public const SETTING_CABINET_IsREADABLE = "isReadable";
  public const SETTING_CABINET_IsWRITABLE = "isWritable";
  public const SETTING_CABINET_IsGROUPFOLDER = "isGroupfolder";

  public const CONFIG_CABINETID = "cabinet_id";
  public const CONFIG_CABINETPATH = "cabinet_path";
  public string $CABINET_NAME_INBOX;
  public string $CABINET_NAME_ARCHIVE;

  public const CONFIG_SYS_CABINETAUTOCONFIGURE = "aktnschrnk.autoconfigure";
  public const CONFIG_SYS_CABINETWORKINGDIR = "aktnschrnk.workingdir";

  /**
   * This method retrieves the user's cabinet settings. Or tries to set default ones if not set yet.
   *
   * @return array Key/Value array of following format:
   * {
   *   @type array $SETTING_CABINET {
   *     @type string $SETTING_CABINET_PATH        Path to the working directory of the app.
   *     @type bool $SETTING_CABINET_IsREADY       TRUE if app is ready to use, FALSE if not.
   *     @type bool $SETTING_CABINET_IsCONFIGURED  TRUE if config stored, FALSE if not.
   *     @type bool $SETTING_CABINET_IsEXISTING    TRUE if all folders exists, FALSE if not.
   *     @type bool $SETTING_CABINET_IsREADABLE    TRUE if all folders are at least readable, FALSE if not.
   *     @type bool $SETTING_CABINET_IsWRITABLE    TRUE if all folders are writable, FALSE if not.
   *     @type bool $SETTING_CABINET_IsGROUPFOLDER TRUE if working dir is a groupfolder.
   *   }
   * }
   * 
   * @throws ExBackend If there is no active user, groupfolder app was disabled or user was not allowed to access their own home.
   * 
   */
  public function getCabinetSettings(): Array 
  {
    
    // prepare
    $result = [];
    $cabPath = null;
    $isReady = false;
    $isConfigured = false;
    $isExisting = false;
    $isReadable = false;
    $isWritable = false;
    $isGroupfolder = false;

    // get node
    $cabinet = $this->getCabinet();

    // create default values if NULL
    if ($cabinet === null)
    {

      // request system config
      $sysAutoconfigure = $this->getSystemConfig(self::CONFIG_SYS_CABINETAUTOCONFIGURE);
      $sysWorkingdir = $this->getSystemConfig(self::CONFIG_SYS_CABINETWORKINGDIR);
      if (!Validation::isValidPath($sysWorkingdir))
      {
        $sysAutoconfigure = false;
        $sysWorkingdir = null;
      }

      // create default path
      $cabPath = $this->fileService->concatPath(!$sysWorkingdir 
        ? $this->l->t("Filing Cabinet")
        : $sysWorkingdir);
      $absPath = $this->fileService->concatPath($this->fileService->getUsersHomePath(), $cabPath);
      
      // check suggestion exists
      $node = $this->fileService->getNode($absPath);
      if ($node === null)
      {

        if ($sysAutoconfigure)
        {
          // setup new cabinet, if $sysAutoconfigure TRUE
          $done = $this->setCabinetSettings($absPath, false);
          return array_merge($this->getCabinetSettings(), $done);
        }

      }
      elseif ($this->fileService->isFolder($node))
      {
        // search again with the found node
        $cabinet = $this->cabinetMapper->findByNode($node);
        if ($cabinet !== null)
        {
          // if cabinet found, save found id
          $this->setUserConfig(self::CONFIG_CABINETID, (string)$cabinet->getId());
          return $this->getCabinetSettings();
        }
        $isExisting = true;
        $isReadable = $this->fileService->isFolderReadable($node);
        $isWritable = $this->fileService->isFolderWritable($node);
        $isGroupfolder = $this->fileService->isGroupfolder($node);
      }
      else 
      {
        // suggested folder is a file
        $isExisting = true;
      }

    }
    else 
    {

      $isConfigured = true;

      // check cabinet folder
      try 
      {
        
        $cabinetNode = $this->cabinetMapper->getRegisteredNode($cabinet);
        $cabPath = $this->fileService->getRelativeToUsersHome($cabinetNode->getPath());
        $isExisting = true;

        // check subfolders
        $this->fileService->createSubFolders($cabinetNode, $this->CABINET_NAME_INBOX, $this->CABINET_NAME_ARCHIVE);

        $isReadable = $this->fileService->isFolderReadable($cabinetNode);
        $isWritable = $this->fileService->isFolderWritable($cabinetNode);
        $isGroupfolder = $this->fileService->isGroupfolder($cabinetNode);
        $isReady = $isReadable;

      }
      catch (ExBadRequest) { /* nothing to do, $isExisting already FALSE */ }

    }

    // return cabinet settings
    return [ self::SETTING_CABINET => [
      self::SETTING_CABINET_PATH => $cabPath,
      self::SETTING_CABINET_IsREADY => $isReady,
      self::SETTING_CABINET_IsCONFIGURED => $isConfigured,
      self::SETTING_CABINET_IsEXISTING => $isExisting,
      self::SETTING_CABINET_IsREADABLE => $isReadable,
      self::SETTING_CABINET_IsWRITABLE => $isWritable,
      self::SETTING_CABINET_IsGROUPFOLDER => $isGroupfolder
    ]];

  }

  /**
   * This method stores the user's new cabinet settings.
   *
   * @param string $cabinetPath The path that should set as working dir.
   * @return array Contains info about done action.
   * 
   * @throws ExBackend If there is no active user, or it was not allowed to access their own home, or create new folder at cabinetPath.
   * 
   */
  public function setCabinetSettings(string $cabinetPath, bool $moveCabinet): Array
  {

    // prepare
    $cabinetPath = $this->fileService->getAbsolute($cabinetPath);
    $cabinetChanges = [];

    // get current cabinet
    $cabinet = $this->getCabinet();
    if ($cabinet === null)
    {

      // ################################################## > NOT CONFIGURED
      $newNode = $this->setCabinetPrepareNewNode($cabinetPath);
      $cabinet = $this->cabinetMapper->registerOrSettleIn($newNode);
      $cabinetChanges = [ "cabinet" => "registerOrSettleIn" ];

    }
    else
    {

      // ################################################## > CONFIGURED

      try 
      {

        // get old Node
        $oldNode = $this->cabinetMapper->getRegisteredNode($cabinet);

        if ($moveCabinet)
        {

          // ################## > OLD NODE'S EXISTING, MOVE TO NEW LOCATION
          $this->setCabinetPrepareMoveNode($cabinetPath, $oldNode);
          $cabinet = $this->cabinetMapper->updateRegistered($oldNode, $cabinet);
          $cabinetChanges = [ "cabinet" => "moveOld" ];

        }
        else
        {
          
          // ################## > OLD NODE'S EXISTING, BUT CREATE NEW CABINET
          $newNode = $this->setCabinetPrepareNewNode($cabinetPath);
          $cabinet = $this->cabinetMapper->registerOrSettleIn($newNode);
          $cabinetChanges = [ "cabinet" => "leaveOld_registerOrSettleIn" ];

        }
        
      }
      catch (ExBadRequest) /* a ExBackend should raise nevertheless if thrown */
      {
        
        // ################## > OLD NODE ISN'T EXISTING ANYMORE
        $newNode = $this->setCabinetPrepareNewNode($cabinetPath);
        $this->cabinetMapper->unregister($cabinet);
        $cabinet = $this->cabinetMapper->registerOrSettleIn($newNode);
        $cabinetChanges = [ "cabinet" => "oldLost_registerOrSettleIn" ];

      }

    }
    
    // save userconfig
    $this->setUserConfig(self::CONFIG_CABINETID, $cabinet->id);
    return $cabinetChanges;

  }

  // TODO: we need a CronJob, that periodically scans each Cabinet, if the registeredNode existing furthermore, if not remove Cabinet and ALL data.

  /**
   * This helper-method creates or gets a Folder at given path, and create subfolders (inbox & archive).
   *
   * @param string $path The path in which the desired Folder is to be created.
   * @return Folder The desired Folder.
   * 
   * @throws ExBackend If user doesn't have permission to create folder at desired path.
   * 
   */
  private function setCabinetPrepareNewNode(string $path): Folder
  {

    // get Node (return NULL if it's not existing)
    $newNode = $this->fileService->getFolder($path);
    if ($newNode === null)
    {
      // create the Folder and sub folders
      $newNode = $this->fileService->createFolder($path, 
        $this->CABINET_NAME_INBOX, $this->CABINET_NAME_ARCHIVE);
    }
    else 
    {
      // create only the sub folders
      $this->fileService->createSubFolders($newNode, 
        $this->CABINET_NAME_INBOX, $this->CABINET_NAME_ARCHIVE);
    }

    return $newNode;

  }

  /**
   * This helper-method moves Folder to new location & backups probably existing Folder at new location.
   *
   * @param string $path The path the old Folder is to be moved.
   * @return void
   * 
   * @throws ExBackend If unsupported Node type, user doesn't permitted to move Node to given path.
   * 
   */
  private function setCabinetPrepareMoveNode(string $newPath, Folder $oldNode)
  {

    // get Node (return NULL if it's not existing)
    $newNode = $this->fileService->getFolder($newPath);
    if ($newNode !== null)
    {

      if ($this->fileService->isGroupfolder($newNode))
      {
        throw new ExBadRequest("cannot replace groupfolder");
      }

      // check $newPath is a cabinet
      $newCabinet = $this->cabinetMapper->findByNode($newNode);
      if ($newCabinet === null)
      {
        // we had to move the existing Node to a "backup" location
        $this->fileService->moveBackup($newNode);
      }
      else 
      {
        // we cannot move another cabinet, this would destroy its data
        throw new ExResourceInUse("cannot move a cabinet away", [ "path" => $newPath, "existingCabId" => $newCabinet->getId() ]);
      }

    }

    if ($this->fileService->isGroupfolder($oldNode)) 
    {
      throw new ExBadRequest("cannot move groupfolder");
    }

    // move the old node to newPath location
    $this->fileService->moveNode($oldNode, $newPath);

    return $newNode;

  }

  // ####################################################################################

  private ?Cabinet $Cache_Cabinet = null;
  private ?Folder $Cache_WorkDir = null;

  /**
   * Load cabinetId and find Cabinet-row in database.
   * @return Cabinet|null The current Cabinet, NULL if not available.
   * 
   */
  private function getCabinet(): Cabinet|null
  {

    // return cached cabinet if available
    if ($this->Cache_Cabinet !== null)
    {
      return $this->Cache_Cabinet;
    }

    // get NodeId of cabinet, return NULL if not stored yet
    $cabinetId = $this->getUserConfig(self::CONFIG_CABINETID);
    if ($cabinetId === null) { return null; }
    
    // find cabinet row, returns NULL if not found
    $this->Cache_Cabinet = $this->cabinetMapper->findById((int)$cabinetId);
    return $this->Cache_Cabinet;

  }

  /**
   * This method returns the app directory.
   *
   * @return Folder The Folder node of the current filing cabinet.
   * 
   * @throws ExBackend If groupfolder app isn't installed or Cabinet is invalid.
   * @throws ExBadRequest If groupfolder id not found, groupfolder not accessible or folder not found.
   * 
   */
  private function getCabinetWorkingDir(): Folder 
  {
    
    // return cached working dir if available
    if ($this->Cache_WorkDir !== null)
    {
      return $this->Cache_WorkDir;
    }

    // get Node of cabinet
    $cabWorkDirPath = $this->getUserConfig(self::CONFIG_CABINETPATH);
    $cabWorkDirNode = empty($cabWorkDirPath) ? null : $this->fileService->getFolder($cabWorkDirPath);
    if ($cabWorkDirNode === null)
    {
      $cabinet = $this->getCabinet();
      if ($cabinet === null)
      {
        throw new ExBadRequest("no Cabinet set");
      }

      $cabWorkDirNode = $this->cabinetMapper->getRegisteredNode($cabinet);
      $this->setUserConfig(self::CONFIG_CABINETPATH, $cabWorkDirNode->getPath());
    }
    
    $this->Cache_WorkDir = $cabWorkDirNode;
    return $this->Cache_WorkDir;

  }

  /**
   * This method returns the archive Folder.
   * 
   * @return Folder The Folder node of the current archive Folder.
   * 
   * @throws ExBackend If groupfolder app isn't installed or Cabinet is invalid.
   * @throws ExBadRequest If groupfolder id not found, groupfolder not accessible or folder not found.
   */
  private function getCabinetArchiveFolder(): Folder
  {
    try 
    {
      $node = $this->getCabinetWorkingDir();
      return $node->get($this->CABINET_NAME_ARCHIVE);
    }
    catch (\Throwable)
    {
      throw new ExBadRequest("archive folder not found");
    }
  }

  /**
   * This method returns the inbox Folder.
   * 
   * @return Folder The Folder node of the current inbox Folder.
   * 
   * @throws ExBackend If groupfolder app isn't installed or Cabinet is invalid.
   * @throws ExBadRequest If groupfolder id not found, groupfolder not accessible or folder not found.
   */
  private function getCabinetInboxFolder(): Folder
  {
    try 
    {
      $node = $this->getCabinetWorkingDir();
      return $node->get($this->CABINET_NAME_INBOX);
    }
    catch (\Throwable)
    {
      throw new ExBadRequest("inbox folder not found");
    }
  }

  #endregion
  #region Pickers

  public const PICKFILE_CONTENTNODES = "contentNodes";
  public const PICKFILE_PARENTNODES = "parentNodes";
  public const PICKFILE_SELECTED = "selected";

  /**
   * This method open a specified path and returns an array with directory information.
   * @param string $path The desired path to a folder.
   * @return array Key/Value array of following format:
   * {
   *   @type array $PICKFILE_CONTENTNODES Array with all children nodes (as JSON).
   *   @type array $PICKFILE_PARENTNODES  Array with parent nodes (as JSON).
   *   @type array $PICKFILE_SELECTED     Array with info about specified path.
   * }
   * 
   */
  public function pickFile(string $path, bool $createNew = false): Array 
  {

    // prepare specified folder
    $absolutePath = $this->fileService->concatPath($this->fileService->getUsersHomePath(), $path);
    $pathNode = $this->fileService->getFolder($absolutePath);
    if ($pathNode === null)
    {
      if ($createNew)
      {

        $parentNode = $this->fileService->getFolder(dirname($absolutePath));
        if ($parentNode === null) 
        {
          throw new ExBadRequest("creation only if parent is existing", [ "path" => $path, "creation" => true ]);
        }
        $pathNode = $this->fileService->createFolder($absolutePath);

      }
      else
      {
        throw new ExBadRequest("specified path doesn't exist", [ "path" => $path ]);
      }
    }
    
    // get listing
    $content = $this->fileService->getFolderContent($pathNode, true);
    $cabinet = $this->cabinetMapper->findByNode($pathNode);

    // get parent folders
    $parents = [];
    $isRoot = $path === "/";
    if (!$isRoot)
    {
      $segments = array_filter(explode("/", $path));
      while (!empty($segments))
      {
        $parentPath = $this->fileService->concatPath(implode("/", $segments));
        $parentName = basename(array_pop($segments));
        
        $parents[] = [
          "path" => $parentPath,
          "name" => $parentName
        ];
      }
      $parents = array_reverse($parents);
    }

    return [
      self::PICKFILE_CONTENTNODES => $content[FileService::FOLDERCONTENT_CHILDREN],
      self::PICKFILE_PARENTNODES => $parents,
      self::PICKFILE_SELECTED => [
        "name" => $isRoot ? null : basename($path),
        "path" => $path,
        "hasChildren" => $content[FileService::FOLDERCONTENT_HASCHILDREN],
        "hasFolders" => $content[FileService::FOLDERCONTENT_HASFOLDERS],
        "isGroupfolder" => $content[FileService::FOLDERCONTENT_ISGROUPFOLDER],
        "isCabinet" => $cabinet !== null
      ]
    ];

  }

  #endregion

  #endregion
  #region Inbox

  public const INBOX_ITEMS = "inboxItems";

  /**
   * This method get InboxEntries from cabinet inbox Folder.
   *
   * @return array Key/Value array of following format:
   * {
   *   @type array $INBOX_ITEMS Array with InboxItem.
   * }
   * 
   * @throws ExBackend If groupfolder app isn't installed or Cabinet is invalid.
   * @throws ExBadRequest If groupfolder id not found, groupfolder not accessible or folder not found.
   * 
   */
  public function getInbox(): Array
  {

    // find all files inside inbox
    $inboxNode = $this->getCabinetInboxFolder();
    $allNodes = $this->fileService->getFolderAllFiles($inboxNode);

    $inboxItems = array_map(function ($node) { return InboxItem::fromNode($node, $this->fileService); }, $allNodes);

    return [
      self::INBOX_ITEMS => $inboxItems
    ];

  }

  #endregion

}

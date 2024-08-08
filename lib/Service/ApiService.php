<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Service;

use OCA\Aktenschrank\AppInfo\Application;
use OCA\Aktenschrank\Db\Cabinet;
use OCA\Aktenschrank\Db\CabinetMapper;
use OCA\Aktenschrank\Exceptions\ExBackend;
use OCA\Aktenschrank\Exceptions\ExBadRequest;

use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserSession;

class ApiService
{

  private CabinetMapper $cabinetMapper;

  private IConfig $appConfig;
  private IL10N $l;
  private IUserSession $userSession;
  private FileService $fileService;
  
  public function __construct(

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
		$userTimezone = $this->appConfig->getUserValue($userUID, 'core', 'timezone', $defaultTimeZone);

    // get language preference
		$userLanguage = $this->appConfig->getUserValue($userUID, 'core', 'lang', null);

    // return
    return [ self::SETTING_LOCALIZATION => [
      self::SETTING_LOCALIZATION_TIMEZONE => $userTimezone,
      self::SETTING_LOCALIZATION_LANGUAGE => $userLanguage
    ]];

  }

  #endregion
  #region UserConfig

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
  private function setUserConfig(string $key, string $value)
  {
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
  public const SETTING_CABINET_IsWRITABLE = "isWritable";

  public const CONFIG_CABINETID = "cabinet_id";
  public string $CABINET_NAME_INBOX;
  public string $CABINET_NAME_ARCHIVE;

  /**
   * This method retrieves the user's cabinet settings. Or tries to set default ones if not set yet.
   *
   * @return array Key/Value array of following format:
   * {
   *   @type array $SETTING_CABINET {
   *     @type string $SETTING_CABINET_PATH         Path to the working directory of the app.
   *     @type string $SETTING_CABINET_IsREADY      TRUE if app is ready to use, FALSE if not.
   *     @type string $SETTING_CABINET_IsCONFIGURED TRUE if config stored, FALSE if not.
   *     @type string $SETTING_CABINET_IsEXISTING   TRUE if all folders exists, FALSE if not.
   *     @type string $SETTING_CABINET_IsWRITABLE   TRUE if all folders are writable, FALSE if not.
   *   }
   * }
   * 
   * @throws ExBackend If there is no active user, or it was not allowed to access their own home.
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
    $isWritable = false;

    // get node
    $cabinet = $this->getCabinet();

    // create default values if NULL
    if ($cabinet === null)
    {

      // create default path
      $cabPath = $this->fileService->concatPath($this->l->t("Filing Cabinet"));
      $absPath = $this->fileService->concatPath($this->fileService->getUsersHomePath(), $cabPath);
      
      // check suggestion exists
      $node = $this->fileService->getNodeByPath($absPath);
      if ($node === null)
      {

        // TODO: autoconfigure
        // TODO: setCabinet(string absolutePath, out $cabinet)

      }
      elseif ($this->fileService->isFolder($node))
      {
        // search again with the found nodeId
        $cabinet = $this->cabinetMapper->findByNodeId((int)$node->getId());
        if ($cabinet === null) 
        { 
          // if not found, set at least nodeId
          $cabinet = new Cabinet(); 
          $cabinet->setNodeId($node->getId());
        }
        else 
        {
          // if cabinet found, save found id
          $this->setUserConfig(self::CONFIG_CABINETID, (string)$cabinet->getId());
          $isConfigured = true;
        }
      }
      else 
      {
        // suggested folder is a file
        $isExisting = true;
        $isWritable = false;
      }

    }
    else 
    {
      $isConfigured = true;
    }

    // check cabinet
    if ($cabinet !== null) 
    { 

      // check cabinet folder
      $cabinetNode = $this->fileService->getFolderById($cabinet->getNodeId());
      if ($cabinetNode !== null) 
      {
        $cabPath = $this->fileService->concatPath($this->fileService->getRelativeToUsersHome($cabinetNode->getPath()));
        $isExisting = true;
        
        // check subfolders if configured
        if ($isConfigured)
        {
          try 
          {
            // create subfolders
            $inboxPath = $this->fileService->concatPath($cabinetNode->getPath(), self::$CABINET_NAME_INBOX);
            $archivePath = $this->fileService->concatPath($cabinetNode->getPath(), self::$CABINET_NAME_ARCHIVE);
            $inboxNode = $this->fileService->createFolder($inboxPath);
            $archiveNode = $this->fileService->createFolder($archivePath);

            // check permissons
            $isWritable = $this->fileService->checkNodePermissions($inboxNode)
              && $this->fileService->checkNodePermissions($archiveNode);
            $isReady = $isWritable;
          }
          catch (\Exception) { /* nothing to do (isWritable already false) */}
        }
        else 
        {
          // check permissions of cabinet only
          $isWritable = $this->fileService->checkNodePermissions($cabinetNode);
        }
      }

    }

    // return cabinet settings
    return [ self::SETTING_CABINET => [
      self::SETTING_CABINET_PATH => $cabPath,
      self::SETTING_CABINET_IsREADY => $isReady,
      self::SETTING_CABINET_IsCONFIGURED => $isConfigured,
      self::SETTING_CABINET_IsEXISTING => $isExisting,
      self::SETTING_CABINET_IsWRITABLE => $isWritable
    ]];

  }

  // ####################################################################################

  private ?Cabinet $Cache_Cabinet = null;

  /**
   * Load cabinetId and find Cabinet-row in database.
   * @return Cabinet|null The current Cabinet, NULL if not available.
   * 
   */
  public function getCabinet(): Cabinet|null
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
    $this->Cache_Cabinet = $this->cabinetMapper->findByNodeId((int)$cabinetId);
    return $this->Cache_Cabinet;

  }


  /* TODO */
  /**
   * This method returns the path to the app directory.
   *
   * @return string|null Path to the directory, NULL if failed or not configured yet.
   * 
   */
  private function getCabinetPath(): ?string 
  {
    try 
    {
      $homePath = $this->fileService->getUsersHomePath();
      $test = $this->fileService->getFolderByPath($homePath);
      $xxxx = $test->getDirectoryListing();
      
      $cabinetPath = $this->getUserConfig(self::CONFIG_CABINETID);
      return $this->fileService->concatPath($homePath, $cabinetPath);
    }
    catch (\Throwable)
    {
      return null;
    }
  }

  /* TODO */
  /**
   * This method returns the path to the archive directory.
   *
   * @param string $workingDir Custom path to working dir, instead path from user config.
   * @return string|null Path to the directory, NULL if failed or not configured yet.
   * 
   */
  private function getCabinetArchivePath(string $workingDir = null): ?string
  {
    try 
    {
      $cabinetPath = $workingDir === null ? $this->getCabinetPath() : $workingDir; 
      return $this->fileService->concatPath($cabinetPath, $this->CABINET_NAME_ARCHIVE);
    }
    catch (\Throwable)
    {
      return null;
    }
  }

  /* TODO */
  /**
   * This method returns the path to the inbox directory.
   *
   * @param string $workingDir Custom path to working dir, instead path from user config.
   * @return string|null Path to the directory, NULL if failed or not configured yet.
   * 
   */
  private function getCabinetInboxPath(string $workingDir = null): ?string 
  {
    try 
    {
      $cabinetPath = $workingDir === null ? $this->getCabinetPath() : $workingDir; 
      return $this->fileService->concatPath($cabinetPath, $this->CABINET_NAME_INBOX);
    }
    catch (\Throwable)
    {
      return null;
    }
  }

  #endregion

  #endregion

}

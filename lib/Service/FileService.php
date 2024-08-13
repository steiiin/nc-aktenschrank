<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Service;

use OCA\Aktenschrank\AppInfo\Application;
use OCA\Aktenschrank\Exceptions\ExBackend;
use OCA\Aktenschrank\Exceptions\ExBadRequest;

use OCP\Files\Node;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;

use OCP\Files\NotFoundException;
use OCP\IGroup;

class FileService
{

  private IConfig $appConfig;
  private IGroupManager $groupManager;
  private IUserSession $userSession;
  private IRootFolder $rootFolder;
  
  public function __construct(
    IConfig $appConfig,
    IGroupManager $groupManager,
    IUserSession $userSession,
    IRootFolder $rootFolder
  ) {
    $this->appConfig = $appConfig;
    $this->groupManager = $groupManager;
    $this->userSession = $userSession;
    $this->rootFolder = $rootFolder;
  }

  #region Nodes

  /**
   * This method returns a Node for specified path.
   *
   * @param string $path Path to desired Node.
   * @param bool $throwErrors TRUE, if errors (notfound) should raise an exception, or simply return NULL.
   * @return Node|null Desired Node, NULL if not $throwErrors FALSE and error occured.
   * 
   * @throws ExBadRequest If specified path not found.
   * 
   */
  public function getNode(string $path, bool $throwErrors = false): ?Node
  {
    try 
    {
      return $this->rootFolder->get($path);
    }
    catch (\Exception $ex)
    {
      if ($throwErrors)
      {
        throw new ExBadRequest("specified path not found", [ "path" => $path ], $ex);
      }
      return null;
    }
  }
   /**
   * This method returns a Folder for specified path.
   *
   * @param string $path Path to desired Folder.
   * @param bool $throwErrors TRUE, if errors (notfound) should raise an exception, or simply return NULL.
   * @return Folder|null Desired Folder, NULL if not $throwErrors FALSE and error occured.
   * 
   * @throws ExBadRequest If $throwErrors TRUE and specified path not found .
   * 
   */
  public function getFolder(string $path, bool $throwErrors = false): ?Folder
  {
    $node = $this->getNode($path, $throwErrors);
    if ($this->isFolder($node)) { return $node; }
    if ($throwErrors)
    {
      throw new ExBadRequest("specified path points to file", [ "path" => $path ]);
    }
    else
    {
      return null;
    }
  }

  // ####################################################################################

  /**
   * This method moves a Node to a new location.
   *
   * @param Node $node The Node that is to be moved.
   * @param string $newPath The path where the Node is to be moved.
   * @return void
   * 
   * @throws ExBackend If unsupported Node type, user doesn't permitted to move Node to given path.
   * @throws ExBadRequest If specified new path already occupied.
   * 
   */
  public function moveNode(Node $node, string $newPath) 
  {

    // prepare move
    $sourceIsFile = $this->isFile($node);
    $targetParentPath = dirname($newPath);
    $targetIsFile = $sourceIsFile && !empty(pathinfo($newPath, PATHINFO_EXTENSION));
    
    if ($sourceIsFile && !$targetIsFile)
    {

      // parent path IS parent path
      $targetParentPath = $newPath;

      // create actual target path
      $newPath = $this->concatPath($targetParentPath, basename($node->getPath()));
      
    }

    // fail if target's already existing
    if ($this->getNode($newPath) !== null)
    {
      throw new ExBadRequest("new path already occupied", [ 
        "oldPath" => $node->getPath(),
        "newPath" => $newPath]);
    }

    // create parent folder
    $this->createFolder($targetParentPath);

    // move node
    try 
    {
      $node->move($newPath);
    }
    catch (\Exception $ex)
    {
      throw new ExBackend("could not move Folder", [ 
        "oldPath" => $node->getPath(),
        "newPath" => $newPath], $ex);
    }

  }

  /**
   * This method moves a Node to almost same location, but with ".oldXXXXXXXXXXXX".
   *
   * @param Node $node The Node that is to be moved.
   * @return void
   * 
   * @throws ExBackend If unsupported Node type, user doesn't permitted to move Node to given path.
   * 
   */
  public function moveBackup(Node $node) 
  {

    $date = date('Ymd'); // Get current date in yyyymmdd format
    $rand = str_pad("".random_int(0, 999999), 3, "0", STR_PAD_LEFT); // Generate a six-digit random number
    $append = ".backup{$date}{$rand}";

    if ($this->isFile($node))
    {
      $nodePath = $node->getPath();
      $newPath = $this->concatPath(dirname($nodePath), 
        pathinfo($nodePath, PATHINFO_FILENAME).$append.".".
        pathinfo($nodePath, PATHINFO_EXTENSION));
    }
    else 
    {
      $newPath = $node->getPath() . $append . "/";
    }
    
    $this->moveNode($node, $newPath);

  }

  #region Folder

  public const FOLDERCONTENT_CHILDREN = "children";
  public const FOLDERCONTENT_HASCHILDREN = "hasChildren";
  public const FOLDERCONTENT_HASFOLDERS = "hasFolders";
  public const FOLDERCONTENT_ISGROUPFOLDER = "isGroupfolder";
  
  /**
   * This method open a node and get all children.
   *
   * @param Node $node The desired Node.
   * @param bool $asJson If TRUE the nodes are returned as JSON, otherwise the Node-references.
   * @return array Key/Value array of following format:
   * {
   *   @type array $FOLDERCONTENT_CHILDREN     Array with all children nodes (!$asJson) or JSON arrays ($asJson).
   *   @type bool $FOLDERCONTENT_HASCHILDREN   TRUE if Node contains children.
   *   @type bool $FOLDERCONTENT_HASFOLDERS    TRUE if Node contains sub folders.
   *   @type bool $FOLDERCONTENT_ISGROUPFOLDER TRUE if Node is a groupfolder.
   * }
   * 
   * @throws ExBadRequest If path not exists, or path leads to file.
   * 
   */
  public function getFolderContent(Node $node, bool $asJson = false): Array 
  {

    // only proceed if Folder
    if ($this->isFolder($node)) 
    {

      /** @var Folder $node */

      $content = [];
      $listing = $node->getDirectoryListing();

      $children = 0;
      $subfolders = 0;

      // return either JSON or File/Folder
      foreach ($listing as $n)
      {
        $children++;
        if ($this->isFolder($n)) { $subfolders++; }
        $content[] = $asJson ? $this->getNodeAsJson($n) : $n;
      }

      // sort $content if JSON
      if ($asJson)
      {

        usort($content, function($a, $b) 
        {
          // first compare by type
          $typeComparison = strcmp($b["type"], $a["type"]); /* desc */
          if ($typeComparison !== 0) {
              return $typeComparison;
          }

          // if types are equal, compare by name
          return strcmp($a["name"], $b["name"]);
        });

      }

      // create result
      $hasChildren = $children > 0;
      $hasFolders = $subfolders > 0;

      return [
        self::FOLDERCONTENT_CHILDREN => $content,
        self::FOLDERCONTENT_HASCHILDREN => $hasChildren,
        self::FOLDERCONTENT_HASFOLDERS => $hasFolders,
        self::FOLDERCONTENT_ISGROUPFOLDER => $this->isGroupfolder($node)
      ];

    }
    else 
    {
      throw new ExBadRequest("specified path leads to a file", [ "path" => $node->getPath() ]);
    }
    
  }

  /**
   * Checks if the specified Node is a Folder.
   *
   * @param Node $node The desired Node.
   * @return bool TRUE if a Folder, FALSE if not.
   * 
   */
  public function isFolder(?Node $node): bool
  {
    if ($node === null) { return false; }
    return $node->getType() === Node::TYPE_FOLDER;
  }
  
  /**
   * This method checks if specified Folder is readable.
   *
   * @param Folder $node The Folder that is to be checked.
   * @return bool TRUE if readable, FALSE if not.
   * 
   */
  public function isFolderReadable(Folder $node): bool 
  {
    return $node->isReadable();
  }

  /**
   * This method checks if specified Folder is writable.
   *
   * @param Folder $node The Folder that is to be checked.
   * @return bool TRUE if writable, FALSE if not.
   * 
   */
  public function isFolderWritable(Folder $node): bool 
  { 
    return $node->isUpdateable()
      && $node->isDeletable()
      && $node->isCreatable();
  }

  /**
   * This method checks if a specified Folder is a Groupfolder.
   *
   * @param Folder $node The Folder to check.
   * @return bool TRUE, if it is a Groupfolder, FALSE if not.
   * 
   * @throws ExBackend If there is no active user.
   * 
   */
  public function isGroupfolder(Folder $node): bool 
  {

    if (class_exists('\OCA\GroupFolders\Mount\GroupMountPoint'))
    {

      $mountPoint = $node->getMountPoint();

      /** @disregard P1009 The GroupMountPoint class is imported dynamically if available */
      return $mountPoint instanceof \OCA\GroupFolders\Mount\GroupMountPoint;

    }
    else 
    {
      // app not installed
      return false;
    }

  }

  /**
   * This method finds the groupfolder id of specified Folder.
   *
   * @param Folder $node The specified Folder.
   * @return int The desired groupfolder id.
   * 
   * @throws ExBackend If groupfolder app not installed or there is no active user.
   * @throws ExBadRequest If specified folder isn't a groupfolder.
   * 
   */
  public function getGroupfolderId(Folder $node): int 
  {

    if (class_exists('\OCA\GroupFolders\Mount\GroupMountPoint'))
    {

      $mountPoint = $node->getMountPoint();

      /** @disregard P1009 The GroupMountPoint class is imported dynamically if available */
      if ($mountPoint instanceof \OCA\GroupFolders\Mount\GroupMountPoint)
      {
        /** @disregard P1009 */
        return $mountPoint->getFolderId();
      }
      else 
      {
        throw new ExBadRequest("specified folder isn't a groupfolder");
      }

    }
    else 
    {
      throw new ExBackend("the groupfolder app isn't installed");
    }

  }

  /**
   * This method finds the group Folder with the specified id.
   *
   * @param int $groupfolderId The id of desired Folder.
   * @return Folder The Folder of desired id.
   * 
   * @throws ExBadRequest If no groupfolder with specified id found, or found Folder not accessible.
   * @throws ExBackend If groupfolder app not installed.
   * 
   */
  public function getGroupfolderNode(int $groupfolderId): Folder 
  {

    if (class_exists('\OCA\GroupFolders\Folder\FolderManager'))
    {

      /** @disregard P1009 The FolderManager class is imported dynamically if available */
      $folderManager = \OC::$server->get(\OCA\GroupFolders\Folder\FolderManager::class);
      
      foreach ($folderManager->getAllFolders() as $groupfolderInfo)
      {
        if ($groupfolderInfo["id"] === $groupfolderId)
        {
          $absolutePath = $this->getAbsolute($groupfolderInfo["mount_point"]);
          return $this->getFolder($absolutePath, true);
        }
      }

      throw new ExBadRequest("no groupfolder with specified id found", [ "id" => $groupfolderId ]);

    }
    else 
    {
      throw new ExBackend("the groupfolder app isn't installed");
    }

  }

  /**
   * This method create a new folder a specified path.
   *
   * @param string $path Path to the folder.
   * @param array $subfolders Subfolders, that are created in the newly created path. 
   * @return Folder The desired newly created folder.
   * 
   * @throws ExBackend If user is not allowed to create a folder there.
   * 
   */
  public function createFolder(string $path, ... $subfolders): Folder 
  {

    $pathNode = $this->getFolder($path);
    if ($pathNode === null)
    {

      try 
      {

        $path = trim($path, "/");
        $segments = array_filter(explode("/", $path));

        $segmentPath = "";
        foreach ($segments as $segment)
        {
          $segmentPath .= "/$segment";

          try 
          {
            // get node at partial path
            $segmentNode = $this->getFolder($segmentPath, true);
          }
          catch (ExBadRequest)
          {
            // fail if not at least root folder could be opened
            if (($segmentNode ?? null) === null)
            {
              throw new ExBackend("not in correct home folder", [ "path" => $segmentPath ]);
            }

            // create this partial path
            try 
            {
              /** @var Folder $segmentNode */
              $segmentNode = $segmentNode->newFolder($segment);
            }
            catch (\Exception $ex)
            {
              throw new ExBackend("could not create folder", [ "path" => $segmentPath], $ex);
            }
          }
          
        }

        // newly created folder
        $pathNode = $segmentNode;

      }
      catch (\Exception $ex)
      {
        throw new ExBackend("could not create folder at specified location", [ "path" => $path ], $ex);
      }

    }

    // create subfolders
    $this->createSubFolders($pathNode, ... $subfolders);

    // return folder
    return $pathNode;

  }

  /**
   * This method create new folders inside specified Folder.
   *
   * @param array $subfolders Subfolders, that are created in the specified Folder. 
   * @return void
   * 
   */
  public function createSubFolders(Folder $node, ... $subfolders) 
  {

    // create subfolders
    try 
    {
      foreach ($subfolders as $subfolder)
      {
        $node->newFolder($subfolder);
      }
    }
    catch (\Exception $ex) { /* nothing to do: subfolders are optional */ }

  }

  #endregion
  #region File

  /**
   * Checks if the specified Node is a File.
   *
   * @param Node $node The desired Node.
   * @return bool TRUE if a File, FALSE if not.
   * 
   */
  public function isFile(?Node $node): bool
  {
    if ($node === null) { return false; }
    return $node->getType() === Node::TYPE_FILE;
  }

  #endregion

  #region JSON

  /**
   * This method converts a Node to a JSON array.
   * @param Node $node The desired Node.
   * @return Array The desired JSON array.
   * 
   */
  public function getNodeAsJson(Node $node): Array 
  {

    if ($this->isFolder($node))
    {

      // prepare metadata
      $path = $node->getPath();
      $content = $this->getFolderContent($node);

      // create JSON
      return [
        "type" => "folder",
        "name" => $node->getName(),
        "node_id" => $node->getId(),
        "path" => [
          "absolute" => $path,
          "relative" => $this->getRelativeToUsersHome($path)
        ],
        "hasChildren" => $content[self::FOLDERCONTENT_HASCHILDREN],
        "hasFolders" => $content[self::FOLDERCONTENT_HASFOLDERS],
        "isGroupfolder" => $content[self::FOLDERCONTENT_ISGROUPFOLDER]
      ];

    }
    elseif ($this->isFile($node))
    {

      // prepare metadata
      $path = $node->getPath();
      $pathinfo = pathinfo($node->getPath());

      // create JSON
      return [
        "type" => "file",
        "name" => $node->getName(),
        "node_id" => $node->getId(),
        "path" => [
          "absolute" => $path,
          "relative" => $this->getRelativeToUsersHome($path),
          "webdav" => $this->getWebdavPath($path)
        ],
        "time_upload" => $node->getUploadTime(),
        "file_ext" => $pathinfo["extension"],
        "file_name" => $pathinfo["filename"],
        "file_mime" => $node->getMimeType(),
        "file_size" => $node->getSize()
      ];

    }

  }

  #endregion

  #endregion

  #region Path

  /**
   * This method construct a path of specified path segments.
   *
   * @param string $parts Multiple segments of the desired path.
   * @return string The path constructed of the specified $parts.
   * 
   * @throws ExBackend If no parts are specified, or at least one segment empty.
   * 
   */
  public function concatPath(string ...$parts): string 
  {

    if (empty($parts)) {
      throw new ExBackend("At least one path segment must be provided.");
    }

    $paths = [];
    foreach ($parts as $part) {
      $part = trim($part);
      if ($part == "") { throw new ExBackend("A path segment is empty", $parts); }
      $paths[] = $part;
    }
    return rtrim(preg_replace("#/+#", "/", "/".join("/", $paths)), "/");

  }

  /**
   * This method get path relative to users home.
   *
   * @param string $absolutePath The absolute path to a Node.
   * @return string The path relativly to active users home folder.
   * 
   * @throws ExBackend If there is no active user, or it was not allowed to access their own home.
   * 
   */
  public function getRelativeToUsersHome(string $absolutePath): string 
  {

    $usersHomePath = $this->getUsersHomePath();
    $result = strpos($absolutePath, $usersHomePath) === 0
      ? substr($absolutePath, strlen($usersHomePath))
      : $absolutePath;
    return strlen($result) === 0 ? "/" : $result;
      
  }

  /**
   * This method get an absolute path of paths inside users home.
   *
   * @param string $relativePath The relative path to a Node.
   * @return string The absolute path.
   * 
   * @throws ExBackend If there is no active user, or it was not allowed to access their own home.
   * 
   */
  public function getAbsolute(string $relativePath): string 
  {

    $usersHomePath = $this->getUsersHomePath();
    return $this->concatPath($usersHomePath, $relativePath);
      
  }

	/**
	 * This method creates the WebDav path of a specified path.
	 * @param string $path The desired path.
	 * @return string The WebDav path.
   * 
   * @throws ExBackend If there is no active user.
	 * 
	 */
	public function getWebdavPath(string $path): string 
	{
		$scheme = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http"); ;
		$host = $_SERVER["HTTP_HOST"];
		$username = $this->getUsersUID();
    $relapath = ltrim($this->getRelativeToUsersHome($path), "/");
		return "$scheme://$host/remote.php/dav/files/$username/$relapath";
	}

  #endregion

  #region User

  /**
   * This method get home path of currently active user.
   *
   * @return string Path of the home directory.
   * 
   * @throws ExBackend If there is no active user, or it was not allowed to access their own home.
   * 
   */
  public function getUsersHomePath(): string 
  {

    $uid = $this->getUsersUID();
    try 
    {
      /* could throw if user not found, or user is not authorized to access their own home dir > not apps fault */
      $homeNode = $this->rootFolder->getUserFolder($uid); 
      return $homeNode->getPath();
    }
    catch (\Exception $ex)
    {
      throw new ExBackend("could not access home node", [ "user" => $uid ], $ex);
    }
    
  }

  /**
   * This method gets the active user UID.
   * @return string The UID.
   * 
   * @throws ExBackend If there is no active user.
   * 
   */
  public function getUsersUID(): string
  {
    $user = $this->userSession->getUser();
    if ($user === null) { throw new ExBackend("no active user"); }
    return $user->getUID();
  }

  #endregion

}

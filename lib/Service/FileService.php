<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Service;

use OCA\Aktenschrank\AppInfo\Application;
use OCA\Aktenschrank\Exceptions\ExBackend;

use OCP\Files\Node;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserSession;

class FileService
{

  private IConfig $appConfig;
  private IUserSession $userSession;
  private IRootFolder $rootFolder;
  
  public function __construct(
    IConfig $appConfig,
    IUserSession $userSession,
    IRootFolder $rootFolder
  ) {
    $this->appConfig = $appConfig;
    $this->userSession = $userSession;
    $this->rootFolder = $rootFolder;
  }

  #region Nodes

  /**
   * This method checks if a specified path exists.
   *
   * @param string $path  The path to desired folder/file.
   * @return bool TRUE if exists, otherwise FALSE.
   * 
   */
  public function isNodeExisting(string $path): bool 
  {
    return $this->getNodeByPath($path) !== null;
  }

  // ##############################################################################################

  /**
   * This method returns a Folder for specified path.
   *
   * @param string $path Path to desired Folder.
   * @return Folder|null Desired Folder, NULL if not found, or a file.
   * 
   */
  public function getFolderByPath(string $path): ?Folder
  {
    $node = $this->getNodeByPath($path);
    if ($node === null) { return null; }
    if ($node instanceof Folder) { return $node; }
    return null;
  }

  /**
   * This method returns a Folder for specified nodeId.
   *
   * @param int $nodeId  Id of the desired Folder.
   * @return Folder|null Desired Folder, NULL if not found, or a file.
   * 
   */
  public function getFolderById(int $nodeId): ?Folder
  {
    $node = $this->getNodeById($nodeId);
    if ($node === null) { return null; }
    if ($node instanceof Folder) { return $node; }
    return null;
  }

  /**
   * Checks if the specified Node is a Folder.
   *
   * @param Node $node The desired Node.
   * @return bool TRUE if a Folder, FALSE if not.
   * 
   */
  public function isFolder(Node $node): bool
  {
    return $node instanceof Folder;
  }

  // ##############################################################################################

  /**
   * This method checks, if the specified Node is writable.
   *
   * @param Node $node The Node to check.
   * @return bool TRUE if accessible, FALSE if not.
   * 
   */
  public function checkNodePermissions(Node $node): bool 
  {
    if (!$node->isReadable()) { return false; }
    if (!$node->isUpdateable()) { return false; }
    if (!$node->isDeletable()) { return false; }
    if ($node instanceof Folder)
    {
      if (!$node->isCreatable()) { return false; }
    }
    return true;
  }

  // ##############################################################################################

  /**
   * This method create a new folder a specified path.
   *
   * @param string $path  Path to the folder.
   * @return Folder The desired newly created folder.
   * 
   * @throws ExBackend If folder already exists, or user is not allowed to create a folder there.
   * 
   */
  public function createFolder(string $path): Folder 
  {

    if ($this->isNodeExisting(($path)))
    {
      return $this->getFolderByPath($path);
    }

    try 
    {
      return $this->rootFolder->newFolder($path);
    }
    catch (\Exception $ex)
    {
      throw new ExBackend("could not create folder at specified location", [ "path" => $path ], $ex);
    }

  }

  // ##############################################################################################

  /**
   * This method returns the Node for specified path.
   *
   * @param string $path  The path to desired folder/file.
   * @return Node|null  The desired Node, NULL if not found.
   * 
   */
  public function getNodeByPath(string $path): ?Node 
  {

    // ignore root folder
    if ($path === "" || $path === "/") { return null; }
    
    // try to get Node, otherwise return NULL
    try 
    {
      return $this->rootFolder->get($path);
    }
    catch (\Throwable)
    {
      return null;
    }

  }

  /**
   * This method returns the Node for specified node id.
   *
   * @param int $nodeId  The nodeId of desired folder/file.
   * @return Node|null The desired Node, NULL if not found.
   * 
   */
  public function getNodeById(int $nodeId): ?Node 
  {
    
    // try to get Node, otherwise return NULL
    try 
    {
      $nodes = $this->rootFolder->getById($nodeId);
      if (count($nodes)===0) { return null; }
			return $nodes[0];
    }
    catch (\Throwable) { return null; }

  }

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
    return preg_replace('#/+#', '/', '/'.join('/', $paths).'/');

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
    return strpos($absolutePath, $usersHomePath) === 0
      ? substr($absolutePath, strlen($usersHomePath))
      : $absolutePath;
      
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

    $user = $this->userSession->getUser();
    if ($user === null) { throw new ExBackend("no active user"); }

    try 
    {
      /* could throw if user not found, or user is not authorized to access their own home dir > not apps fault */
      $homeNode = $this->rootFolder->getUserFolder($user->getUID()); 
      return $homeNode->getPath();
    }
    catch (\Exception $ex)
    {
      throw new ExBackend("could not access home node", [ "user" => $user->getUID() ], $ex);
    }
    
  }

  #endregion

}

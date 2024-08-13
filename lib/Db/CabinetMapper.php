<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Db;

use OCA\Aktenschrank\Exceptions\ExBackend;
use OCA\Aktenschrank\Service\FileService;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Folder;
use OCP\IDBConnection;

class CabinetMapper extends QBMapper {

	protected FileService $fileService;

	public function __construct(

		FileService $fileService,
		IDBConnection $db

	) {

		parent::__construct($db, self::TABLE, Cabinet::class);
		$this->fileService = $fileService;

	}

	private const TABLE = 'aktnschrnk_cabinets';

    // ##################################################################################

	/**
	 * This method tries to find a Cabinet-row by Cabinet id.
	 *
	 * @param int $cabinetId The id of the desired cabinet.
	 * @return Cabinet|null	The desired Cabinet-row, NULL if not found, or error occured.
	 * 
	 */
	public function findById(int $cabinetId): ?Cabinet 
	{

		try 
		{

			// build query
			$qb = $this->db->getQueryBuilder();
			$qb->select('*')->from(self::TABLE)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($cabinetId, IQueryBuilder::PARAM_INT)));
		
			// find entity
			return $this->findEntity($qb);
			
		} 
		catch (\Throwable)
		{
			return null;
		}

	}

	/**
	 * This method tries to find a Cabinet-row by Node.
	 *
	 * @param Folder $node The Folder of the desired cabinet.
	 * @return Cabinet|null	The desired Cabinet-row, NULL if not found, or error occured.
	 * 
	 */
	public function findByNode(Folder $node): ?Cabinet 
	{

		$path = rtrim($node->getPath(), "/");

		// start query
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from(self::TABLE);

		// switch mount-type
		$isGroupfolder = $this->fileService->isGroupfolder($node);
		if ($isGroupfolder)
		{

			// create query for Groupfolder-Type
			$groupfolderId = $this->fileService->getGroupfolderId($node);
			$qb->where($qb->expr()->eq("mount_type", $qb->createNamedParameter(self::MOUNTTYPE_GROUPFOLDER, IQueryBuilder::PARAM_INT)));
			$qb->andWhere($qb->expr()->eq("mount_gf_id", $qb->createNamedParameter($groupfolderId, IQueryBuilder::PARAM_INT)));
			
		}
		else 
		{

			// create query for Folder-Type
			$qb->where($qb->expr()->eq("mount_type", $qb->createNamedParameter(self::MOUNTTYPE_FOLDER, IQueryBuilder::PARAM_INT)));
			$qb->andWhere($qb->expr()->iLike("mount_path", $qb->createNamedParameter($path, IQueryBuilder::PARAM_STR)));

		}

		// execute query
		try 
		{
			return $this->findEntity($qb);
		}
		catch (\OCP\AppFramework\Db\DoesNotExistException $ex)
		{
			return null;
		}
		catch (\Exception $ex)
		{
			throw new ExBackend("could not find Cabinet", [ "path" => $path ], $ex);
		}

	}

    // ##################################################################################

	private const MOUNTTYPE_FOLDER = 2;
	private const MOUNTTYPE_GROUPFOLDER = 4;

	/**
	 * This method registers a specified Folder.
	 *
	 * @param Folder $node The desired Folder.
	 * @return Cabinet The newly registered Cabinet.
	 * 
	 * @throws ExBackend If database error occured
	 * 
	 */
	public function registerOrSettleIn(Folder $node): Cabinet 
	{

		// find Cabinet
		$cabinet = $this->findByNode($node);
		return $cabinet === null
			? $this->register($node, null)
			: $cabinet;
	
	}

	/**
	 * This method registers a specified Folder as new value in existing Cabinet.
	 *
	 * @param Folder $node The desired Folder that is to be registered.
	 * @param Cabinet $cabinet The existing Cabinet that is to be updated.
	 * @return Cabinet The updated Cabinet.
	 * 
	 */
	public function updateRegistered(Folder $node, Cabinet $cabinet): Cabinet
	{
		return $this->register($node, $cabinet);
	}

	/**
	 * This method deletes specified Cabinet.
	 *
	 * @param Cabinet $cabinet The desired Cabinet that is to be deleted.
	 * @return void
	 * 
	 * @throws ExBackend If removing failed.
	 * 
	 */
	public function unregister(Cabinet $cabinet)
	{
		try 
		{
			$this->delete($cabinet);
		}
		catch (\Exception $ex)
		{
			throw new ExBackend("removing cabinet failed", [], $ex);
		}
	}

	/**
	 * This method registers a specified Folder.
	 *
	 * @param Folder $node The desired Folder.
	 * @param Cabinet $cabinet The Cabinet that should to be updated, or NULL if a new should to be created.
	 * @return Cabinet The newly registered Cabinet.
	 * 
	 * @throws ExBackend If database error occured
	 * 
	 */
	private function register(Folder $node, ?Cabinet $cabinet): Cabinet
	{

		// create new cab if not specified
		$isNew = $cabinet === null;
		if ($isNew)
		{
			$cabinet = new Cabinet();
		}

		// prepare data
		$path = rtrim($node->getPath(), "/");
		$isGroupfolder = $this->fileService->isGroupfolder($node);

		// update/insert Node data
		if ($isGroupfolder)
		{
			$groupfolderId = $this->fileService->getGroupfolderId($node);
			$cabinet->setMountType(self::MOUNTTYPE_GROUPFOLDER);
			$cabinet->setMountPath();
			$cabinet->setMountGfId($groupfolderId);
		}
		else 
		{
			$cabinet->setMountType(self::MOUNTTYPE_FOLDER);
			$cabinet->setMountPath($path);
			$cabinet->setMountGfId();
		}

		try 
		{
			return $isNew ? $this->insert($cabinet) : $this->update($cabinet);
		}
		catch (\Exception $ex)
		{
			throw new ExBackend("error while insert-/updating Cabinet", [], $ex);
		}

	}

	// ##################################################################################

	/**
	 * This method finds the registered Node of specified Cabinet.
	 *
	 * @param Cabinet $cabinet The Cabinet to find the Folder for.
	 * @return Folder The found Folder.
	 * 
	 * @throws ExBackend If groupfolder app isn't installed or Cabinet is invalid.
	 * @throws ExBadRequest If groupfolder id not found, groupfolder not accessible or folder not found.
	 * 
	 */
	public function getRegisteredNode(Cabinet $cabinet): Folder 
	{

		$mountType = $cabinet->getMountType();
		if ($mountType === self::MOUNTTYPE_GROUPFOLDER)
		{

			// find groupfolder
			$groupfolderId = $cabinet->getMountGfId();
			return $this->fileService->getGroupfolderNode($groupfolderId);

		}
		else if ($mountType === self::MOUNTTYPE_FOLDER)
		{

			// find folder
			$path = $cabinet->getMountPath();
			return $this->fileService->getFolder($path, true);

		}
		else 
		{
			throw new ExBackend("cabinet has invalid mount type", [ "mountType" => $mountType ]);
		}

	}

	// public function registerGroupfolder(string $mountPath, int $groupfolderId): Cabinet 
	// {

	// 	$isGroupfolder = $this->fileService->isGroupfolder($newNode);
	// 	if ($isGroupfolder)
	// 	{
	// 	  $groupfolderId = $this->fileService->getGroupfolderId($newNode);
	// 	  $relativePath = $this->fileService->getRelativeToUsersHome($newNode->getPath());
	// 	  $cabinet = $this->cabinetMapper->registerGroupfolder($relativePath, $groupfolderId);
	// 	}
	// 	else 
	// 	{
	// 	  $cabinet = $this->cabinetMapper->registerFolder($newNode->getPath());
	// 	}

	// 	$cabinet = new Cabinet();
	// 	$cabinet->setMountType(self::MOUNTTYPE_GROUPFOLDER);
	// 	$cabinet->setMountPath($mountPath);
	// 	$cabinet->setMountGfId($groupfolderId);
	// }

	// public function registerFolder(string $path): Cabinet 
	// {
	// 	$cabinet = new Cabinet();
	// 	$cabinet->setMountType(self::MOUNTTYPE_FOLDER);
	// 	$cabinet->setMountPath($path);
	// 	$cabinet->setMountGfId(null);
	// }

	/**
	 * This method insert a nodeId.
	 *
	 * @param int $nodeId The desired Node
	 * @return Cabinet The desired Cabinet for specifed nodeId
	 * 
	 * @throws ExBackend If existing Cabinet could not be found or the database could not be accessed.
	 * 
	 */
	// public function registerCabinet(int $nodeId): Cabinet 
	// {
	// 	try 
	// 	{

	// 		$cabinet = new Cabinet();
	// 		$cabinet->setNodeId($nodeId);
	// 		return $this->insert($cabinet);

	// 	} 
	// 	catch (\Exception $ex)
	// 	{
	// 		if (ExBackend::isConstraintError($ex))
	// 		{
	// 			$cabinet = $this->findByNodeId($nodeId);
	// 			if ($cabinet === null)
	// 			{
	// 				throw new ExBackend("could not find existing cabinet", [ "node" => $nodeId ], $ex);
	// 			}
	// 			else 
	// 			{
	// 				return $cabinet;
	// 			}
	// 		}
	// 		else
	// 		{
	// 			throw new ExBackend("failed to insert cabinet", [ "node" => $nodeId ], $ex);
	// 		}
	// 	}
	// }


/*
	public function getAccess(string $cabinetPath): Access 
	{

		$cabinetPath = rtrim($cabinetPath, '/');

		try 
		{
			
			$access = $this->findByPath($cabinetPath);
			if ($access === null)
			{
				$access = new Access();
				$access->setCabinetPath($cabinetPath);
				$access = $this->insert($access);
			}
			return $access;

		} catch (\Exception $ex) { throw new BackendException('AccessMapper', 'getAccess', 'database query for AccessId failed'); }

	}

	public function setAccess(string $cabinet, ?string $oldCabinet = null)
	{

		$newCabinet = rtrim($cabinet, '/');
		$oldCabinet = rtrim($oldCabinet ?? '', '/');

		try 
		{

			$access = $this->findByPath($oldCabinet);
			if (!$access) { return $this->getAccess($newCabinet); }
			{
				$access->setCabinetPath($newCabinet);
				$this->update($access);
				return $access;
			}

		} catch (\Exception $ex) { throw new BackendException('AccessMapper', 'getAccess', 'failed to update the AccessId in the database'); }

	}

    // ##################################################################################

	private function findByPath(string $cabinetPath): ?Access 
	{

		$cabinetPath = rtrim($cabinetPath, '/');
		
		try 
		{

			$qb = $this->db->getQueryBuilder();
			$qb->select('*')
				->from(self::TABLE)
				->where($qb->expr()->eq('cabinet_path', $qb->createNamedParameter($cabinetPath, IQueryBuilder::PARAM_STR)));
		
			return $this->findEntity($qb);
			
		} 
		catch (DoesNotExistException) 
		{
			return null;
		}

	}
 */
}

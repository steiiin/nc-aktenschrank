<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class CabinetMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE, Cabinet::class);
	}
	private const TABLE = 'aktnschrnk_cabinets';

    // ##################################################################################

	/**
	 * This method tries to find a Cabinet-row by NodeId.
	 *
	 * @param int $nodeId The NodeId of the cabinet folder.
	 * @return Cabinet|null	The desired Cabinet-row, NULL if not found, or error occured.
	 * 
	 */
	public function findByNodeId(int $nodeId): ?Cabinet 
	{

		try 
		{

			// build query
			$qb = $this->db->getQueryBuilder();
			$qb->select('*')->from(self::TABLE)
			->where($qb->expr()->eq('cabinet_node', $qb->createNamedParameter($nodeId, IQueryBuilder::PARAM_INT)));
		
			// find entity
			return $this->findEntity($qb);
			
		} 
		catch (\Throwable)
		{
			return null;
		}

	}

    // ##################################################################################



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

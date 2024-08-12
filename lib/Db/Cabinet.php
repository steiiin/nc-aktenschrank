<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Db;

use OCP\AppFramework\Db\Entity;

class Cabinet extends Entity {

	// public $id @Entity
	protected $mountType;
	protected $mountPath;
	protected $mountGfId;

	public function __construct()
	{
		$this->addType('id', 'integer');
		$this->addType('mount_type', 'integer');
		$this->addType('mount_path', 'string');
		$this->addType('mount_gf_id', 'integer');
	}

}

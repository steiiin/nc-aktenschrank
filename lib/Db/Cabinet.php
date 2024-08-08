<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Db;

use OCP\AppFramework\Db\Entity;

class Cabinet extends Entity {

	// public $id @Entity
	protected $nodeId;

	public function __construct()
	{
		$this->addType('id', 'integer');
		$this->addType('nodeId', 'integer');
	}

}

<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Db;

use OCP\AppFramework\Db\Entity;

class Document extends Entity {

    // public $id @Entity
    protected $accessId;
    protected $title;

    protected $timeAdded;
    protected $timeMentioned;
    protected $timeModified;
    protected $timeExpires;

    protected $recipientId;
    protected $originId;
    protected $originContactId;

    protected $tray1;
    protected $tray2;
    protected $tray3;
    protected $groupBy;

    protected $tagType;
    protected $tagStatus;
    protected $tagImportance;

    protected $tags;
    protected $description;
    protected $content;

    protected $pathTemplate;
    protected $pathValue;

    protected $fileSize;
    protected $fileMime;
    protected $fileHash;

    public function __construct()
    {

        $this->addType('id', 'integer');
        $this->addType('accessId', 'integer');
		$this->addType('title', 'string');

        $this->addType('timeAdded', 'integer');
		$this->addType('timeMentioned', 'integer');
		$this->addType('timeModified', 'integer');
		$this->addType('timeExpires', 'integer');

        $this->addType('recipientId', 'integer');
		$this->addType('originId', 'integer');
		$this->addType('originContactId', 'integer');

        $this->addType('tray1', 'string');
		$this->addType('tray2', 'string');
		$this->addType('tray3', 'string');
		$this->addType('groupBy', 'string');

        $this->addType('tagType', 'string');
        $this->addType('tagStatus', 'string');
        $this->addType('tagImportance', 'string');

        $this->addType('tags', 'json');
		$this->addType('description', 'string');
		$this->addType('content', 'string');

        $this->addType('pathTemplate', 'string');
        $this->addType('pathValue', 'string');

        $this->addType('fileSize', 'integer');
        $this->addType('fileMime', 'string');
        $this->addType('fileHash', 'string');

    }

}

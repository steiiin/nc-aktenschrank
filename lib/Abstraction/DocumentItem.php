<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Abstraction;

use OCA\Aktenschrank\Db\Document;
use OCA\Aktenschrank\Service\ApiService;
use OCP\Files\File;

class DocumentItem implements \JsonSerializable
{

    protected $document;
    protected $file;
    protected $preview;

    // protected $linkedDocuments;
    // protected $linkedTasks;

    public function __construct(

        Document $document,
        FileItem $file,
        ?FileItem $preview,
        // Array $linkedDocuments,
        // Array $linkedTasks,

    ) {
        
        $this->document = $document;
        $this->file = $file;
        $this->preview = $preview;

    }

    public function jsonSerialize(): mixed
    {
        return [
            "document" => [
                "id" => $this->document->getId(),
                "accessId" => $this->document->getAccessId(),
                "title" => $this->document->getTitle(),
                "timeAdded" => $this->document->getTimeAdded(),
                "timeMentioned" => $this->document->getTimeMentioned(),
                "timeModified" => $this->document->getTimeModified(),
                "timeExpires" => $this->document->getTimeExpires(),
                "recipientId" => $this->document->getRecipientId(),
                "originId" => $this->document->getOriginId(),
                "originContactId" => $this->document->getOriginContactId(),
                "tray1" => $this->document->getTray1(),
                "tray2" => $this->document->getTray2(),
                "tray3" => $this->document->getTray3(),
                "groupBy" => $this->document->getGroupBy(),
                "tagType" => $this->document->getTagType(),
                "tagStatus" => $this->document->getTagStatus(),
                "tagImportance" => $this->document->getTagImportance(),
                "tags" => $this->document->getTags(),
                "description" => $this->document->getDescription(),
                "content" => $this->document->getContent(),
                "pathTemplate" => $this->document->getPathTemplate(),
                "pathValue" => $this->document->getPathValue(),
                "fileSize" => $this->document->getFileSize(),
                "fileMime" => $this->document->getFileMime(),
                "fileHash" => $this->document->getFileHash()
            ],
            "file" => $this->file,
            "preview" => $this->preview
        ];
    }

}
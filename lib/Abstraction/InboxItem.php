<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Abstraction;

use OCA\Aktenschrank\Db\Document;
use OCA\Aktenschrank\Helpers\Validation;
use OCA\Aktenschrank\Service\FileService;
use OCP\Files\File;

class InboxItem extends DocumentItem
{

    public static function fromNode(File $node, FileService $fileService): InboxItem
    {
        $document = new Document();
        $document->setTitle(Validation::simplifyFilename($node->getName()));
        $document->setTimeAdded($node->getUploadTime());
        $document->setTimeMentioned($node->getUploadTime());
        $document->setTags([]);
        $document->setFileSize($node->getSize());
        $document->setFileMime($node->getMimeType());
        $document->setFileHash($node->getChecksum());

        $file = FileItem::fromFile($node, $fileService);
        $preview = null;

        return new InboxItem($document, $file, $preview);
    }

}
<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Abstraction;

use OCA\Aktenschrank\Service\FileService;
use OCP\Files\Folder;

class FolderItem implements \JsonSerializable {

    public $type = "folder";
    public $name;

    public $node_id;
    public $path_absolute;
    public $path_relative;

    public $hasChildren;
    public $hasFolders;
    public $isGroupfolder;

    public static function fromFolder(Folder $node, FileService $fileService): FolderItem
    {
        $path = $node->getPath();
        $content = $fileService->getFolderContent($node);

        $folderitem = new FolderItem();
        $folderitem->name = $node->getName();
        $folderitem->node_id = $node->getId();
        $folderitem->path_absolute = $path;
        $folderitem->path_relative = $fileService->getRelativeToUsersHome($path);
        $folderitem->hasChildren = $content[FileService::FOLDERCONTENT_HASCHILDREN];
        $folderitem->hasFolders = $content[FileService::FOLDERCONTENT_HASFOLDERS];
        $folderitem->isGroupfolder = $content[FileService::FOLDERCONTENT_ISGROUPFOLDER];
        return $folderitem;
    }

    public function jsonSerialize(): mixed
    {
        return [
            "type" => $this->type,
            "name" => $this->name,
            "node_id" => $this->node_id,
            "path" => [
              "absolute" => $this->path_absolute,
              "relative" => $this->path_relative
            ],
            "hasChildren" => $this->hasChildren,
            "hasFolders" => $this->hasFolders,
            "isGroupfolder" => $this->isGroupfolder
          ];
    }

}
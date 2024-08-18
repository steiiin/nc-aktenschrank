<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Abstraction;

use OCA\Aktenschrank\Service\FileService;
use OCP\Files\File;

class FileItem implements \JsonSerializable {

    public $type = "file";
    public $name;

    public $node_id;
    public $path_absolute;
    public $path_relative;
    public $path_webdav;

    public $time_upload;

    public $file_extn;
    public $file_name;
    public $file_mime;
    public $file_size;

    public static function fromFile(File $node, FileService $fileService): FileItem
    {
        $path = $node->getPath();
        $pathinfo = pathinfo($path);

        $fileitem = new FileItem();
        $fileitem->name = $node->getName();
        $fileitem->node_id = $node->getId();
        $fileitem->path_absolute = $path;
        $fileitem->path_relative = $fileService->getRelativeToUsersHome($path);
        $fileitem->path_webdav = $fileService->getWebdavPath($path);
        $fileitem->time_upload = $node->getUploadTime();
        $fileitem->file_extn = $pathinfo["extension"];
        $fileitem->file_name = $pathinfo["filename"];
        $fileitem->file_mime = $node->getMimeType();
        $fileitem->file_size = $node->getSize();
        return $fileitem;
    }

    public function jsonSerialize(): mixed
    {
        return [
            "type" => $this->type,
            "name" => $this->name,
            "node_id" => $this->node_id,
            "path" => [
              "absolute" => $this->path_absolute,
              "relative" => $this->path_relative,
              "webdav" => $this->path_webdav
            ],
            "time_upload" => $this->time_upload,
            "file_extn" => $this->file_extn,
            "file_name" => $this->file_name,
            "file_mime" => $this->file_mime,
            "file_size" => $this->file_size
          ];
    }

}
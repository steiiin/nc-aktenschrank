<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Exceptions;

class ExResourceInUse extends ApiException 
{
    public function __construct(string $message, Array $data = [], ?\Exception $previous = null) 
    {
        parent::__construct(409, $message, $data, $previous);
    }
}
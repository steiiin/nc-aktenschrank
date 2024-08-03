<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Api;

use OCP\AppFramework\Http\JSONResponse;

class ApiResult {

    public static function json(bool $success, ...$props): JSONResponse 
	{
		return new JSONResponse(array_merge(
			[ "success" => $success ],
			...$props
		));
	}

}

<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Api;

use OCA\Aktenschrank\Exceptions\ApiException;

use OCP\AppFramework\Http\JSONResponse;

class ApiResult {

    public static function json(bool $success, ...$props): JSONResponse 
	{
		return new JSONResponse(array_merge(
			[ "success" => $success ],
			...$props
		));
	}

	public static function error(\Exception $ex)
	{

		if ($ex instanceof ApiException) 
		{

			$statuscode = $ex->getStatusCode();
			$payload = $ex->getPayload();

			// Handle app-specific exceptions
			http_response_code($statuscode);
			header('Content-Type: application/json');
			echo(json_encode(array_merge([ "code" => $statuscode ], $payload)));
			die();

		} 
		else 
		{

			// handle other error
			http_response_code(500);
			header('Content-Type: application/json');
			echo(json_encode([ "code" => 500, "message" => $ex->getMessage() ]));
			die();

		}

	}

}

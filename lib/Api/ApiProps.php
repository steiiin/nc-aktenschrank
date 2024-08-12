<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Api;

use OCA\Aktenschrank\Exceptions\ApiException;

use OCP\AppFramework\Http\JSONResponse;

class ApiProps {

	/**
	 * This method tries to read data transmitted to the API.
	 * @return Array An array with all transmitted data.
	 * 
	 */
	public static function get(): Array
	{

		$props = [];

		// add POST data
		$type = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
		if (str_contains($type, 'application/json')) 
		{
			// decode json body
			$body = file_get_contents('php://input');
			if ($body !== false)
			{
				$json = json_decode($body, true);
				if ($json !== false)
				{
					$props = array_merge($props, $json);
				}
			}
		}
		elseif (str_contains($type, 'multipart/form-data'))
		{
			// read json
			$body = $_POST['json'] ?? false;
			if ($body !== false)
			{
				$json = json_decode($body, true);
				if ($json !== false)
				{
					$props = array_merge($props, $json);
				}
			}
			// read file
			$file = $_FILES['file'] ?? false;
			if ($file !== false)
			{
				$props = array_merge($props, [ 'uploaded_file' => $file ]);
			}
		}

		// add GET data
		$props = array_merge($props, $_GET);

		return $props;

	}

}

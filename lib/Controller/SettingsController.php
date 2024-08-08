<?php

declare(strict_types=1);

namespace OCA\Aktenschrank\Controller;

use OCA\Aktenschrank\AppInfo\Application;
use OCA\Aktenschrank\Api\ApiResult;
use OCA\Aktenschrank\Service\ApiService;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-suppress UnusedClass
 */
class SettingsController extends Controller {

	private ApiService $apiService;

    public function __construct(
        IRequest $request,
        ApiService $apiService
    ) {
        parent::__construct(Application::APP_ID, $request);
        $this->apiService = $apiService;
    }

	#region Settings

	#[NoAdminRequired]
	public function getSettings(): JSONResponse {

        try 
        {
            $localization = $this->apiService->getLocalizationSettings();
            $cabinet = $this->apiService->getCabinetSettings();
            return ApiResult::json(true, $localization, $cabinet);
        } 
        catch (\Exception $ex) { ApiResult::error($ex); }
        
	}

	#endregion

}

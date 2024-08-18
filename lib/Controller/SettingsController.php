<?php

declare(strict_types=1);

namespace OCA\Aktenschrank\Controller;

use OCA\Aktenschrank\AppInfo\Application;
use OCA\Aktenschrank\Api\ApiResult;
use OCA\Aktenschrank\Api\ApiProps;
use OCA\Aktenschrank\Exceptions\ExBadRequest;
use OCA\Aktenschrank\Helpers\Validation;
use OCA\Aktenschrank\Service\ApiService;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * API-Controller for settings endpoints.
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

    #[NoAdminRequired]
    public function setSettings(): JSONResponse {

        try 
        {
            $this->prepareSetSettings(ApiProps::get(), $cabinetPath, $moveCabinet);
            $cabinet = $this->apiService->setCabinetSettings($cabinetPath, $moveCabinet);
            return ApiResult::json(true, $cabinet);
        }
        catch (\Exception $ex) { ApiResult::error($ex); }

    }

    #region Prepare

    /**
     * Validates the specified props and populate the specified references.
     *
     * @param mixed $props JSON parameters to be validated for "pickFile".
     * @param string|null &$path Reference, that is populated with validated path-value. 
     * @return void
     * 
     */
    private function prepareSetSettings(mixed $props, ?string &$cabinetPath, ?bool &$moveCabinet)
    {

        if (!is_array($props)) { throw new ExBadRequest('no props specified'); }

        // cabinet
        $cabinet = $props['cabinet'] ?? null;
        if (!is_array($cabinet)) { throw new ExBadRequest('cabinet props not available'); }
        
        $cabinetPath = $cabinet['path'] ?? null;
        if (!Validation::isValidPath($cabinetPath)) { throw new ExBadRequest('specified path is not a valid path'); }

        $moveCabinet = ($cabinet['moveExisting'] ?? false) === true;

    }

    #endregion

	#endregion

    #region FilePicker

    #[NoAdminRequired]
	public function pickFile(): JSONResponse {

        try 
        {
            $this->preparePickFile(ApiProps::get(), $path, $createNew);
            $pick = $this->apiService->pickFile($path, $createNew);
            return ApiResult::json(true, $pick);
        } 
        catch (\Exception $ex) { ApiResult::error($ex); }
        
	}

    #region Prepare

    /**
     * Validates the specified props and populate the specified references.
     *
     * @param mixed $props JSON parameters to be validated for "pickFile".
     * @param string|null &$path Reference, that is populated with validated path-value. 
     * @return void
     * 
     */
    private function preparePickFile(mixed $props, ?string &$path, ?bool &$createNew)
    {

        if (!is_array($props)) { throw new ExBadRequest('no props specified'); }

        $path = $props['path'] ?? null;
        if (!Validation::isValidPath($path)) { throw new ExBadRequest('specified path is no valid path'); }

        $createNew = $props['create'] ?? false;

    }

    #endregion

    #endregion

}

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
 * API-Controller for documents endpoints.
 */
class DocumentsController extends Controller {

	private ApiService $apiService;

    public function __construct(
        IRequest $request,
        ApiService $apiService
    ) {
        parent::__construct(Application::APP_ID, $request);
        $this->apiService = $apiService;
    }

	#region Inbox

	#[NoAdminRequired]
	public function getInbox(): JSONResponse {

        try 
        {
            $inboxContent = $this->apiService->getInbox();
            return ApiResult::json(true, $inboxContent);
        } 
        catch (\Exception $ex) { ApiResult::error($ex); }
        
	}

	#endregion

}

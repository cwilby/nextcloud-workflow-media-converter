<?php

namespace OCA\WorkflowMediaConverter\Controller;

use OCA\WorkflowMediaConverter\Service\ConfigService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class AdminSettingsController extends Controller {
	private $configService;

	public function __construct($AppName, IRequest $request, ConfigService $configService) {
		parent::__construct($AppName, $request);
		$this->configService = $configService;
	}

	public function getSettings(): DataResponse {
		return new DataResponse($this->configService->getAdminConfig());
	}

	public function updateSettings(array $values): DataResponse {
		$this->configService->setAppConfig($values);

		return new DataResponse($this->configService->getAdminConfig());
	}
}

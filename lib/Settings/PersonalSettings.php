<?php

namespace OCA\WorkflowMediaConverter\Settings;

use OCA\WorkflowMediaConverter\AppInfo\Application;
use OCA\WorkflowMediaConverter\Service\ConfigService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Settings\ISettings;

class PersonalSettings implements ISettings {
	private $initialStateService;
	private $configService;

	public function __construct(IInitialState $initialStateService, ConfigService $configService) {
		$this->initialStateService = $initialStateService;
		$this->configService = $configService;
	}

	public function getForm(): TemplateResponse {
		$this->initialStateService->provideInitialState('personal-config', $this->configService->getCurrentUserConfig());
		$this->initialStateService->provideInitialState('threadLimit', $this->configService->getAppConfigValue('threadLimit'));

		return new TemplateResponse(Application::APP_ID, 'personalSettings');
	}

	public function getSection(): string {
		return Application::APP_ID;
	}

	public function getPriority() {
		return 50;
	}
}

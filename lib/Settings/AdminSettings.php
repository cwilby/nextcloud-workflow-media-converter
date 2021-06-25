<?php

namespace OCA\WorkflowMediaConverter\Settings;

use OCA\WorkflowMediaConverter\AppInfo\Application;
use OCA\WorkflowMediaConverter\Service\ConfigService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings
{
    private IInitialState $initialStateService;
    private ConfigService $configService;

    public function __construct($AppName, IInitialState $initialStateService, ConfigService $configService)
    {
        $this->appName = $AppName;
        $this->initialStateService = $initialStateService;
        $this->configService = $configService;
    }

    public function getForm(): TemplateResponse
    {
        $this->initialStateService->provideInitialState('admin-config', $this->configService->getAdminConfig());

        return new TemplateResponse(Application::APP_ID, 'adminSettings');
    }

    public function getSection(): string
    {
        return Application::APP_ID;
    }

    public function getPriority()
    {
        return 50;
    }
}

<?php

namespace OCA\WorkflowMediaConverter\Controller;

use OCA\WorkflowMediaConverter\Service\ConfigService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class PersonalSettingsController extends Controller
{
    private ConfigService $configService;

    public function __construct($AppName, IRequest $request, ConfigService $configService)
    {
        parent::__construct($AppName, $request);
        $this->configService = $configService;
    }

    /**
     * @NoAdminRequired
     */
    public function getSettings(): DataResponse
    {
        return new DataResponse($this->configService->getCurrentUserConfig());
    }

    /**
     * @NoAdminRequired
     */
    public function updateSettings(array $values): DataResponse
    {
        if (isset($values['rules'])) {
            $this->configService->setConfigValueJson('rules', $values['rules']);
        }

        return new DataResponse($this->configService->getCurrentUserConfig());
    }
}

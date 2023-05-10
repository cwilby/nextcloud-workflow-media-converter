<?php

namespace OCA\WorkflowMediaConverter\AppInfo;

use OCA\WorkflowMediaConverter\Listener\RegisterFlowOperationsListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'workflow_media_converter';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(
			RegisterOperationsEvent::class,
			RegisterFlowOperationsListener::class
		);
	}

	public function boot(IBootContext $context): void {
	}
}

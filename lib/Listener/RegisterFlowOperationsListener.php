<?php

namespace OCA\WorkflowMediaConverter\Listener;

use OCA\WorkflowMediaConverter\AppInfo\Application;
use OCA\WorkflowMediaConverter\Operation\ConvertMediaOperation;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;
use Psr\Container\ContainerInterface;

class RegisterFlowOperationsListener implements IEventListener {
	private ContainerInterface $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	public function handle(Event $event): void {
		if (!($event instanceof RegisterOperationsEvent)) {
			return;
		}

		$event->registerOperation($this->container->get(ConvertMediaOperation::class));

		Util::addScript(Application::APP_ID, Application::APP_ID . '-operator');
	}
}

<?php

namespace OCA\WorkflowMediaConverter\Listener;

use OCA\WorkflowMediaConverter\AppInfo\Application;
use OCA\WorkflowMediaConverter\Operation\ConvertMediaOperation;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class RegisterFlowOperationsListener implements IEventListener {
	private ContainerInterface $container;

	public function __construct(ContainerInterface $container, LoggerInterface $logger) {
		$this->container = $container;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if (!($event instanceof RegisterOperationsEvent)) {
			return;
		}

		$event->registerOperation($this->container->get(ConvertMediaOperation::class));

		$this->logger->info('Registering workflow operations for ' . Application::APP_ID);
		$this->container
			->get(\OCA\WorkflowEngine\Manager::class)
			->registerCheck($this->container->get(\OCA\WorkflowMediaConverter\Check\PathName::class));

		Util::addScript(Application::APP_ID, Application::APP_ID . '-operator');
	}
}

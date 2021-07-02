<?php

namespace OCA\WorkflowMediaConverter\Listener;

use OCA\WorkflowMediaConverter\AppInfo\Application;
use OCA\WorkflowMediaConverter\Operation\ConvertMediaOperation;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IServerContainer;
use OCP\Util;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;

class RegisterFlowOperationsListener implements IEventListener
{
    private IServerContainer $container;

    public function __construct(IServerContainer $container)
    {
        $this->container = $container;
    }

    public function handle(Event $event): void
    {
        if (!($event instanceof RegisterOperationsEvent)) {
            return;
        }

        $event->registerOperation($this->container->get(ConvertMediaOperation::class));

        Util::addScript(Application::APP_ID, Application::APP_ID . '-operator');
    }
}

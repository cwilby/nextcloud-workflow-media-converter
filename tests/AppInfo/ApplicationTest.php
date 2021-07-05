<?php

namespace OCA\WorkflowMediaConverter\Tests\AppInfo;

use Mockery;
use OCA\WorkflowMediaConverter\AppInfo\Application;
use OCA\WorkflowMediaConverter\Listener\RegisterFlowOperationsListener;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase {
	protected function tearDown(): void {
		Mockery::close();
	}

	public function testApplicationHasConstantAppId() {
		$this->assertEquals('workflow_media_converter', Application::APP_ID);
	}

	public function testApplicationGivesParentAppId() {
		$application = new Application();

		$this->assertEquals(Application::APP_ID, $application->getContainer()->getAppName());
	}

	public function testRegisterFunctionRegistersWorkflowEventListener() {
		$contextSpy = \Mockery::spy(IRegistrationContext::class);

		$application = new Application();

		$application->register($contextSpy);

		$contextSpy->shouldHaveReceived()->registerEventListener(
			RegisterOperationsEvent::class,
			RegisterFlowOperationsListener::class
		);

		$this->assertTrue(true);
	}

	public function testBootFunctionExists() {
		$contextSpy = \Mockery::spy(IBootContext::class);

		$application = new Application();

		$application->boot($contextSpy);

		$this->assertTrue(true);
	}
}

<?php

namespace OCA\WorkflowMediaConverter\Operation;

use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\GenericEvent;
use OCA\WorkflowEngine\Entity\File;
use OCA\WorkflowMediaConverter\AppInfo\Application;
use OCA\WorkflowMediaConverter\BackgroundJobs\ConvertMediaJob;
use OCP\Files\Folder;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IRuleMatcher;
use OCP\WorkflowEngine\ISpecificOperation;
use Psr\Log\LoggerInterface;

class ConvertMediaOperation implements ISpecificOperation {
	private $jobList;
	private $urlGenerator;
	private $logger;
	private $l;

	public function __construct(IJobList $jobList, IURLGenerator $urlGenerator, LoggerInterface $logger, IL10N $l) {
		$this->jobList = $jobList;
		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
		$this->l = $l;
	}

	public function validateOperation(string $name, array $checks, string $operation): void {
		//
	}

	public function getDisplayName(): string {
		return $this->l->t('Convert media');
	}

	public function getEntityId(): string {
		return File::class;
	}

	public function getDescription(): string {
		return $this->l->t('Convert video/audio files using FFmpeg.');
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath(Application::APP_ID, 'icon.svg');
	}

	public function isAvailableForScope(int $scope): bool {
		return $scope === IManager::SCOPE_USER;
	}

	public function onEvent(string $eventName, Event $event, IRuleMatcher $ruleMatcher): void {
		try {
			$this->handleEvent($eventName, $event, $ruleMatcher);
		} catch (\Throwable $e) {
			$this->logger->error("({$e->getCode()}) :: {$e->getMessage()}", ['eventName' => $eventName]);
		}
	}

	private function handleEvent(string $eventName, GenericEvent $event, IRuleMatcher $ruleMatcher): void {
		$node = $event->getSubject();

		if ($eventName === '\OCP\Files::postRename') {
			$node = $node[1];
		}

		$path = $node->getPath();
		$ncFolder = explode('/', $path, 4)[2];

		if ($ncFolder !== 'files' || $node instanceof Folder) {
			return;
		}

		$flows = $ruleMatcher->getFlows(false);

		$originalFileMode = $targetFileMode = null;

		foreach ($flows as $flow) {
			$config = json_decode($flow['operation'], true);

			$outputExtension = $config['outputExtension'];
			$postConversionSourceRule = $config['postConversionSourceRule'];
			$postConversionSourceRuleMoveFolder = $config['postConversionSourceRuleMoveFolder'];
			$postConversionOutputRule = $config['postConversionOutputRule'];
			$postConversionOutputRuleMoveFolder = $config['postConversionOutputRuleMoveFolder'];
			$postConversionOutputConflictRule = $config['postConversionOutputConflictRule'];
			$postConversionOutputConflictRuleMoveFolder = $config['postConversionOutputConflictRuleMoveFolder'];

			if ($originalFileMode === 'keep' && $targetFileMode === 'preserve') {
				break;
			}

			if (empty($outputExtension) || empty($postConversionSourceRule) || empty($postConversionOutputRule)) {
				return;
			}

			$this->jobList->add(ConvertMediaJob::class, [
				'path' => $path,
				'outputExtension' => $outputExtension,
				'postConversionSourceRule' => $postConversionSourceRule,
				'postConversionSourceRuleMoveFolder' => $postConversionSourceRuleMoveFolder,
				'postConversionOutputRule' => $postConversionOutputRule,
				'postConversionOutputRuleMoveFolder' => $postConversionOutputRuleMoveFolder,
				'postConversionOutputConflictRule' => $postConversionOutputConflictRule,
				'postConversionOutputConflictRuleMoveFolder' => $postConversionOutputConflictRuleMoveFolder,
			]);
		}
	}
}

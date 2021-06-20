<?php

namespace OCA\WorkflowMediaConverter\Operation;

use MicrosoftAzure\Storage\Common\Logger;
use OCA\WorkflowMediaConverter\Support\Util;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\GenericEvent;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\IURLGenerator;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IRuleMatcher;
use OCP\WorkflowEngine\ISpecificOperation;
use Psr\Log\LoggerInterface;

class ConvertMediaOperation implements ISpecificOperation
{
    private IJobList $jobList;
    private IURLGenerator $urlGenerator;
    private LoggerInterface $logger;

    public function __construct(IJobList $jobList, IURLGenerator $urlGenerator, LoggerInterface $logger)
    {
        $this->jobList = $jobList;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
    }

    public function validateOperation(string $name, array $checks, string $operation): void
    {
        // i think we check if the file extension matches the rules here.
    }

    public function getDisplayName(): string
    {
        return $this->l->t('Media Conversion');
    }

    public function getDescription(): string
    {
        return $this->l->t('Convert video/audio media via FFmpeg on upload and write.');
    }

    public function getIcon(): string
    {
        return $this->urlGenerator->imagePath('workflow_media_converter', 'app.svg');
    }

    public function isAvailableForScope(int $scope): bool
    {
        return $scope === IManager::SCOPE_USER;
    }

    public function onEvent(string $eventName, Event $event, IRuleMatcher $ruleMatcher): void
    {
        try {
            $this->handleEvent($eventName, $event, $ruleMatcher);
        } catch (\Throwable $e) {
            $this->logger->error("({$e->getCode()}) :: $e->getMessage()", ['eventName' => $eventName]);
        }
    }

    private function handleEvent(string $eventName, GenericEvent $event, IRuleMatcher $ruleMatcher): void
    {
        $node = $event->getSubject();

        if ($eventName === '\OCP\Files::postRename') {
            $node = $node[1];
        }

        $path = $node->getPath();
        $ncFolder = Util::getNextcloudFolderName($path);

        if ($ncFolder !== 'files' || $node instanceof Folder) {
            return;
        }

        $matches = $ruleMatcher->getFlows(false);

        $originalFileMode = $targetFileMode = null;

        foreach ($matches as $match) {
            $fileModes = explode(';', $match['operation']);

            $originalFileMode = $originalFileMode === 'keep' ?: $fileModes[0];
            $targetFileMode = $targetFileMode === 'preserve' ?: $fileModes[1];

            if ($originalFileMode === 'keep' && $targetFileMode === 'preserve') {
                break;
            }
        }

        if (empty($originalFileMode) || empty($targetFileMode)) {
            return;
        }

        $this->jobList->add(ConvertMediaJob::class, [
            'path' => $path,
            'originalFileMode' => $originalFileMode,
            'targetFileMode' => $targetFileMode
        ]);
    }
}

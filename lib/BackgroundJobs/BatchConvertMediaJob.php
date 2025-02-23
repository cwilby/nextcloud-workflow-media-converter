<?php

namespace OCA\WorkflowMediaConverter\BackgroundJobs;

use OCA\WorkflowMediaConverter\Service\ConfigService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;

class BatchConvertMediaJob extends QueuedJob {
	private $logger;
	private $rootFolder;
	private $jobList;
	private $configService;
	private $batchId;
	private $userFolder;
	private $convertMediaInSubFolders;
	private $userId;
	private $sourceFolderPath;
	private $sourceExtension;
	private $outputExtension;
	private $status;
	private $additionalConversionFlags;
	private $additionalInputConversionFlags;
	private $additionalOutputConversionFlags;
	private $sourceFolder;
	private $postConversionSourceRule;
	private $postConversionSourceRuleMoveFolder;
	private $postConversionOutputRule;
	private $postConversionOutputRuleMoveFolder;
	private $postConversionOutputConflictRule;
	private $postConversionOutputConflictRuleMoveFolder;

	public $unconvertedMedia = [];

	public function __construct(
		ITimeFactory $time,
		LoggerInterface $logger,
		IRootFolder $rootFolder,
		IJobList $jobList,
		ConfigService $configService,
	) {
		parent::__construct($time);
		$this->logger = $logger;
		$this->rootFolder = $rootFolder;
		$this->jobList = $jobList;
		$this->configService = $configService;
	}

	public function run($arguments) {
		try {
			$this->logger->info(BatchConvertMediaJob::class . ' started');
			$this
				->parseArguments($arguments)
				->findUnconvertedMediaInFolder($this->sourceFolder)
				->queueUnconvertedMediaForConversion();

			$this->configService->setBatchStatus($this->batchId, 'converting');
		} catch (\Throwable $e) {
			$this->configService->updateBatch($this->batchId, [
				'status' => 'failed',
				'error' => [
					'code' => $e->getCode(),
					'message' => $e->getMessage()
				]
			]);
			$this->logger->error((string)$e);
		} finally {
			$this->logger->info(BatchConvertMediaJob::class . ' finished');
		}
	}

	public function parseArguments($arguments) {
		$this->userId = (string)($arguments['uid'] ?? $arguments['user_id']);

		$this->configService->setUserId($this->userId);
		$this->batchId = $arguments['id'];
		$this->status = $arguments['status'];
		$this->userFolder = "/{$this->userId}/files";
		$this->additionalConversionFlags = (string)($arguments['additionalConversionFlags'] ?? '');
		$this->additionalInputConversionFlags = (string)($arguments['additionalInputConversionFlags'] ?? '');
		$this->additionalOutputConversionFlags = (string)($arguments['additionalOutputConversionFlags'] ?? '');
		$this->sourceFolderPath = $this->prependUserFolder($arguments['sourceFolder']);
		$this->convertMediaInSubFolders = $arguments['convertMediaInSubFolders'];
		$this->sourceExtension = strtolower($arguments['sourceExtension']);
		$this->outputExtension = strtolower($arguments['outputExtension']);
		$this->postConversionSourceRule = $arguments['postConversionSourceRule'];
		$this->postConversionSourceRuleMoveFolder = $this->prependUserFolder($arguments['postConversionSourceRuleMoveFolder']);
		$this->postConversionOutputRule = $arguments['postConversionOutputRule'];
		$this->postConversionOutputRuleMoveFolder = $this->prependUserFolder($arguments['postConversionOutputRuleMoveFolder']);
		$this->postConversionOutputConflictRule = $arguments['postConversionOutputConflictRule'];
		$this->postConversionOutputConflictRuleMoveFolder = $this->prependUserFolder($arguments['postConversionOutputConflictRuleMoveFolder']);

		$this->sourceFolder = $this->rootFolder->get($this->sourceFolderPath);

		$this->configService->setBatchStatus($this->batchId, 'seeking');

		return $this;
	}

	public function findUnconvertedMediaInFolder(Folder $folder) {
		foreach ($folder->getDirectoryListing() as $node) {
			if ($this->convertMediaInSubFolders && $node instanceof Folder) {
				$this->findUnconvertedMediaInFolder($node);
			}

			if (!($node instanceof File)) {
				continue;
			}

			$filename = $node->getName();
			$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

			if ($extension !== $this->sourceExtension) {
				continue;
			}

			$filenameNoExtension = str_replace(".{$extension}", '', $filename);
			$possibleOutputFilename = $filenameNoExtension . ".{$this->outputExtension}";

			if (!$folder->nodeExists($possibleOutputFilename)) {
				$this->unconvertedMedia[] = $node;
			}
		}

		return $this;
	}

	public function queueUnconvertedMediaForConversion() {
		$count = 0;

		if (count($this->unconvertedMedia) === 0) {
			$this->configService->setBatchStatus($this->batchId, 'completed');
			return $this;
		}

		foreach ($this->unconvertedMedia as $node) {
			$count++;
			$this->jobList->add(ConvertMediaJob::class, [
				'uid' => $this->userId,
				'batch_id' => $this->batchId,
				'path' => $node->getPath(),
				'outputExtension' => $this->outputExtension,
				'additionalConversionFlags' => $this->additionalConversionFlags,
				'additionalInputConversionFlags' => $this->additionalInputConversionFlags,
				'additionalOutputConversionFlags' => $this->additionalOutputConversionFlags,
				'postConversionSourceRule' => $this->postConversionSourceRule,
				'postConversionSourceRuleMoveFolder' => $this->postConversionSourceRuleMoveFolder,
				'postConversionOutputRule' => $this->postConversionOutputRule,
				'postConversionOutputRuleMoveFolder' => $this->postConversionOutputRuleMoveFolder,
				'postConversionOutputConflictRule' => $this->postConversionOutputConflictRule,
				'postConversionOutputConflictRuleMoveFolder' => $this->postConversionOutputConflictRuleMoveFolder
			]);
		}

		$this->configService->updateBatch($this->batchId, ['unconverted' => $count]);

		return $this;
	}

	protected function prependUserFolder($path) {
		if (empty($path)) {
			return null;
		}

		return $this->userFolder . '/' . ltrim(str_replace($this->userFolder, '', $path), '/');
	}
}

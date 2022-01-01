<?php

namespace OCA\WorkflowMediaConverter\BackgroundJobs;

use OC\Files\Filesystem;
use OCA\WorkflowMediaConverter\Factory\ProcessFactory;
use OCA\WorkflowMediaConverter\Factory\ViewFactory;
use OCA\WorkflowMediaConverter\Service\ConfigService;
use OCA\WorkflowMediaConverter\Exceptions\MediaConversionThrottledException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ConvertMediaJob extends QueuedJob {
	private $logger;
	private $rootFolder;
	private $configService;
	private $viewFactory;
	private $processFactory;
    private $jobList;

	public function __construct(ITimeFactory $time, LoggerInterface $logger, IRootFolder $rootFolder, ConfigService $configService, ViewFactory $viewFactory, ProcessFactory $processFactory, IJobList $jobList) {
		parent::__construct($time);
		$this->rootFolder = $rootFolder;
		$this->logger = $logger;
		$this->configService = $configService;
		$this->viewFactory = $viewFactory;
		$this->processFactory = $processFactory;
        $this->jobList = $jobList;
	}

	protected function run($arguments) {
		$this->logger->info(ConvertMedia::class . ' started');
		try {
			$this
				->parseArguments($arguments)
                ->lockConversion()
				->convertMedia()
				->handlePostConversion()
                ->unlockConversion()
				->notifyBatchSuccess();
        } catch (\MediaConversionLockedException $e) {
            $this->logger->info(ConvertMedia::class . ' requeued - another conversion is currently running.');
        } catch (\Throwable $e) {
            $this->configService->releaseConversionLock();
			$eType = get_class($e);
			$this->notifyBatchFail($e);
			$this->logger->error("[{$eType}] :: ({$e->getCode()}) :: {$e->getMessage()} :: {$e->getTraceAsString()}");
		} finally {
			$this->logger->info(ConvertMedia::class . ' finished');
		}
	}

	public function parseArguments($arguments) {
		$this->path = (string)$arguments['path'];
		$this->userId = (string)($arguments['uid'] ?? $arguments['user_id'] ?? '');
		if (empty($this->userId)) {
			$this->userId = explode('/', $this->path, 4)[1];
		}

		$this->userFolder = "/{$this->userId}/files";

		Filesystem::init($this->userId, $this->userFolder);

		$this->configService->setUserId($this->userId);
		$this->batchId = (string)($arguments['batch_id'] ?? '');

        $this->convertMediaInParallel = (bool)($arguments['convertMediaInParallel'] ?? false);
		$this->postConversionSourceRule = (string)$arguments['postConversionSourceRule'];
		$this->postConversionSourceRuleMoveFolder = $this->prependUserFolder($arguments['postConversionSourceRuleMoveFolder']);
		$this->postConversionOutputRule = (string)$arguments['postConversionOutputRule'];
		$this->postConversionOutputRuleMoveFolder = $this->prependUserFolder($arguments['postConversionOutputRuleMoveFolder']);
		$this->postConversionOutputConflictRule = (string)$arguments['postConversionOutputConflictRule'];
		$this->postConversionOutputConflictRuleMoveFolder = $this->prependUserFolder($arguments['postConversionOutputConflictRuleMoveFolder']);
		$this->outputExtension = (string)$arguments['outputExtension'];

		$this->sourceFile = $this->rootFolder->get($this->path);
		$this->sourceFolder = dirname($this->path);
		$this->sourceFolderView = $this->viewFactory->create($this->sourceFolder);
		$this->sourceFilename = basename($this->path);
		$this->sourceExtension = pathinfo($this->sourceFilename, PATHINFO_EXTENSION);
		$this->tempSourcePath = $this->sourceFolderView->toTmpFile($this->sourceFilename);
		$this->tempSourceFilename = basename($this->tempSourcePath);
		$this->tempOutputPath = str_replace(".{$this->sourceExtension}", ".{$this->outputExtension}", $this->tempSourcePath);
		$this->tempOutputFilename = basename($this->tempOutputPath);
		$this->outputFilePath = str_replace(".{$this->sourceExtension}", ".{$this->outputExtension}", $this->path);
		$this->outputFileName = basename($this->outputFilePath);

		if ($this->postConversionOutputRule === 'move') {
			$this->outputFolder = $this->rootFolder->get($this->postConversionOutputRuleMoveFolder);
		} else {
			$this->outputFolder = $this->sourceFile->getParent();
		}

		return $this;
	}

    public function lockConversion()
    {
		$adminAllowedParallelConversion = $this->configService->getAppConfigValue('convertMediaInParallel', false);

        if ($this->convertMediaInParallel && $adminAllowedParallelConversion) {
            return $this; // (continue with conversion)
        }

        if ($this->configService->checkConversionLock()) {
            $this->jobList->add(ConvertMediaJob::class, [
				'uid' => $this->userId,
				'batch_id' => $this->batchId,
				'path' => $this->path,
				'outputExtension' => $this->outputExtension,
                'convertMediaInParallel' => $this->convertMediaInParallel,
				'postConversionSourceRule' => $this->postConversionSourceRule,
				'postConversionSourceRuleMoveFolder' => $this->postConversionSourceRuleMoveFolder,
				'postConversionOutputRule' => $this->postConversionOutputRule,
				'postConversionOutputRuleMoveFolder' => $this->postConversionOutputRuleMoveFolder,
				'postConversionOutputConflictRule' => $this->postConversionOutputConflictRule,
				'postConversionOutputConflictRuleMoveFolder' => $this->postConversionOutputConflictRuleMoveFolder
			]);

            throw new MediaConversionLockedException();
        }

        $this->configService->setConversionLock();

        return $this;
    }

	public function convertMedia() {
		$threads = $this->configService->getAppConfigValue('threadLimit', 0);

		$command = "ffmpeg -threads $threads -i {$this->tempSourcePath} {$this->tempOutputPath}";

		$process = $this->processFactory->create($command);

		$process->run();

		if (!$process->isSuccessful()) {
			throw new ProcessFailedException($process);
		}

		return $this;
	}

	public function handlePostConversion() {
		$this->writePostConversionOutputFile();
		$this->handlePostConversionSourceFile();

		return $this;
	}

	public function writePostConversionOutputFile() {
		if ($this->outputFolder->nodeExists($this->outputFileName)) {
			$existingFile = $this->outputFolder->get($this->outputFileName);

			switch ($this->postConversionOutputConflictRule) {
				case 'move':
					$existingFile->move($this->removeDoubleSlashes($this->postConversionOutputConflictRuleMoveFolder) . '/' . $this->outputFileName);
					break;
				case 'overwrite':
					$existingFile->delete();
					break;
			}
		}

		return $this->writeFileSafe($this->outputFolder, $this->tempOutputPath, $this->outputFileName);
	}

	public function handlePostConversionSourceFile() {
		switch ($this->postConversionSourceRule) {
			case 'delete':
				$this->sourceFile->delete();
				break;
			case 'move':
				$this->sourceFile->move($this->removeDoubleSlashes($this->postConversionSourceRuleMoveFolder) . '/' . $this->sourceFilename);
				break;
			default:
				break;
		}
	}

	public function notifyBatchSuccess() {
		if (!$this->batchId) {
			return;
		}

		$batch = $this->configService->getBatch($this->batchId);

		$this->configService->updateBatch($this->batchId, [
			'converted' => ($batch['converted'] ?? 0) + 1,
			'status' => ($batch['converted'] + 1) === $batch['unconverted'] ? 'finished' : 'converting'
		]);
	}

    public function unlockConversion() {
        if ($this->convertMediaInParallel) {
            return $this;
        }

        $this->configService->releaseConversionLock();

        return $this;
    }

	public function notifyBatchFail(\Throwable $e) {
		if (!$this->batchId) {
			return;
		}

		$batch = $this->configService->getBatch($this->batchId);

		$this->configService->updateBatch($this->batchId, [
			'status' => 'has-failures',
			'failed' => ($batch['failed'] ?? 0) + 1,
			'errors' => array_merge(($batch['errors'] ?? []), ["{$e->getMessage()} -- Error code {$e->getCode()}"])
		]);
	}

	public function writeFileSafe($folder, $tempFile, $filename) {
		$fileNameNoExtension = str_replace(".{$this->outputExtension}", '', $this->outputFileName);

		$index = 1;

		$newFileName = $filename;

		while ($this->outputFolder->nodeExists($newFileName)) {
			$newFileName = "{$fileNameNoExtension} ({$index}).{$this->outputExtension}";
		}

		$view = $this->viewFactory->create($folder->getPath());

		$view->fromTmpFile($tempFile, $newFileName);

		return $this;
	}

	protected function removeDoubleSlashes($path) {
		return preg_replace('#/+#', '/', $path);
	}

	protected function prependUserFolder($path) {
		if (empty($path)) {
			return null;
		}

		return $this->userFolder . '/' . ltrim(str_replace($this->userFolder, '', $path), '/');
	}
}

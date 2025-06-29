<?php

namespace OCA\WorkflowMediaConverter\BackgroundJobs;

use OCA\WorkflowMediaConverter\Exceptions\MediaConversionLockedException;
use OCA\WorkflowMediaConverter\Factory\ProcessFactory;
use OCA\WorkflowMediaConverter\Factory\ViewFactory;
use OCA\WorkflowMediaConverter\Service\ConfigService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
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
	private $convertMediaInParallel;
	private $outputFilePath;
	private $path;
	private $userId;
	private $userFolder;
	private $batchId;
	private $postConversionSourceRule;
	private $postConversionSourceRuleMoveFolder;
	private $postConversionOutputRule;
	private $postConversionOutputRuleMoveFolder;
	private $postConversionOutputConflictRule;
	private $postConversionOutputConflictRuleMoveFolder;
	private $postConversionTimestampRule;
	private $additionalConversionFlags;
	private $additionalInputConversionFlags;
	private $additionalOutputConversionFlags;
	private $ffmpegPath;
	private $outputExtension;
	private $sourceFile;
	private $sourceFolder;
	private $sourceFolderView;
	private $sourceFilename;
	private $sourceExtension;
	private $tempSourcePath;
	private $tempSourceFilename;
	private $tempOutputPath;
	private $tempOutputFilename;
	private $outputFileName;
	private $outputFolder;

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
		$this->logger->info(ConvertMediaJob::class . ' started - converting ' . $arguments['path']);
		try {
			$this
				->parseArguments($arguments)
				->lockConversion()
				->convertMedia()
				->handlePostConversion()
				->unlockConversion()
				->notifyBatchSuccess();
		} catch (MediaConversionLockedException $e) {
			$this->logger->info(ConvertMediaJob::class . ' requeued for ' . $arguments['path']);
		} catch (\Throwable $e) {
			$this->configService->setAppConfigValue('conversionLock', 'no');
			$eType = get_class($e);
			$this->notifyBatchFail($e);
			$this->logger->error("[{$eType}] :: ({$e->getCode()}) :: {$e->getMessage()} :: {$e->getTraceAsString()}");
		} finally {
			$this->logger->info(ConvertMediaJob::class . ' finished - output file: ' . $this->outputFilePath);
		}
	}

	public function parseArguments($arguments) {
		$adminSettings = $this->configService->getAdminConfig();

		$this->path = (string)$arguments['path'];
		$this->userId = (string)($arguments['uid'] ?? $arguments['user_id'] ?? '');
		if (empty($this->userId)) {
			$this->userId = explode('/', $this->path, 4)[1];
		}

		$this->userFolder = "/{$this->userId}/files";

		$this->configService->setUserId($this->userId);
		$this->batchId = (string)($arguments['batch_id'] ?? '');

		$this->postConversionSourceRule = (string)$arguments['postConversionSourceRule'];
		$this->postConversionSourceRuleMoveFolder = $this->prependUserFolder($arguments['postConversionSourceRuleMoveFolder']);
		$this->postConversionOutputRule = (string)$arguments['postConversionOutputRule'];
		$this->postConversionOutputRuleMoveFolder = $this->prependUserFolder($arguments['postConversionOutputRuleMoveFolder']);
		$this->postConversionOutputConflictRule = (string)$arguments['postConversionOutputConflictRule'];
		$this->postConversionOutputConflictRuleMoveFolder = $this->prependUserFolder($arguments['postConversionOutputConflictRuleMoveFolder']);
		$this->postConversionTimestampRule = isset($arguments) && isset($arguments['postConversionTimestampRule']) ? (string)$arguments['postConversionTimestampRule'] : 'conversionTime';
		$this->outputExtension = (string)$arguments['outputExtension'];
		$this->convertMediaInParallel = isset($adminSettings) && isset($adminSettings['convertMediaInParallel']) ? (bool)$adminSettings['convertMediaInParallel'] : false;
		$this->ffmpegPath = isset($adminSettings) && isset($adminSettings['ffmpegPath']) && !empty($adminSettings['ffmpegPath']) ? $adminSettings['ffmpegPath'] : 'ffmpeg';
		$this->additionalConversionFlags = (string)($arguments['additionalConversionFlags'] ?? '');
		$this->additionalInputConversionFlags = (string)($arguments['additionalInputConversionFlags'] ?? '');
		$this->additionalOutputConversionFlags = (string)($arguments['additionalOutputConversionFlags'] ?? '');

		$this->sourceFile = $this->rootFolder->get($this->path);
		$this->sourceFolder = dirname($this->path);
		$this->sourceFolderView = $this->viewFactory->create($this->sourceFolder);
		$this->sourceFilename = basename($this->path);
		$this->sourceExtension = pathinfo($this->sourceFilename, PATHINFO_EXTENSION);
		$this->tempSourcePath = $this->sourceFolderView->toTmpFile($this->sourceFilename);
		$this->tempSourceFilename = basename($this->tempSourcePath);
		$this->tempOutputPath = str_replace(".{$this->sourceExtension}", "_out_.{$this->outputExtension}", $this->tempSourcePath);
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

	public function lockConversion() {
		if ($this->parallelConversionEnabled()) {
			return $this;
		}

		if ($this->conversionLockIsActive()) {
			$this->jobList->add(ConvertMediaJob::class, [
				'uid' => $this->userId,
				'batch_id' => $this->batchId,
				'path' => $this->path,
				'additionalConversionFlags' => $this->additionalConversionFlags,
				'additionalInputConversionFlags' => $this->additionalInputConversionFlags,
				'additionalOutputConversionFlags' => $this->additionalOutputConversionFlags,
				'outputExtension' => $this->outputExtension,
				'convertMediaInParallel' => $this->convertMediaInParallel,
				'postConversionSourceRule' => $this->postConversionSourceRule,
				'postConversionSourceRuleMoveFolder' => $this->postConversionSourceRuleMoveFolder,
				'postConversionOutputRule' => $this->postConversionOutputRule,
				'postConversionOutputRuleMoveFolder' => $this->postConversionOutputRuleMoveFolder,
				'postConversionOutputConflictRule' => $this->postConversionOutputConflictRule,
				'postConversionOutputConflictRuleMoveFolder' => $this->postConversionOutputConflictRuleMoveFolder,
				'postConversionTimestampRule' => $this->postConversionTimestampRule
			]);

			throw new MediaConversionLockedException();
		}

		$this->setConversionLockActive(true);

		return $this;
	}

	public function convertMedia() {
		$process = $this->processFactory->create(
			command: $this->getConversionCommand(flagsBeforeInput: false)
		);

		$process->run();

		if (!$process->isSuccessful()) {
			$process = $this->processFactory->create(
				command: $this->getConversionCommand(flagsBeforeInput: true)
			);

			$process->run();

			if (!$process->isSuccessful()) {
				throw new ProcessFailedException($process);
			}
		}

		return $this;
	}

	private function getConversionCommand($flagsBeforeInput = false) {
		$threads = $this->configService->getAppConfigValue('threadLimit', 0);

		$additionalConversionFlags = empty($this->additionalConversionFlags) ? '' : " {$this->additionalConversionFlags}";
		$additionalInputConversionFlags = empty($this->additionalInputConversionFlags) ? '' : " {$this->additionalInputConversionFlags}";
		$additionalOutputConversionFlags = empty($this->additionalOutputConversionFlags) ? '' : " {$this->additionalOutputConversionFlags}";

		$commands = [];

		$commands[] = 'umask 0077';

		$ffmpegCommand = "{$this->ffmpegPath} -threads {$threads}";

		if (!empty($additionalConversionFlags)) {
			if ($flagsBeforeInput) {
				$ffmpegCommand .= " {$additionalConversionFlags} -i {$this->tempSourcePath}";
			} else {
				$ffmpegCommand .= " -i {$this->tempSourcePath} {$additionalConversionFlags}";
			}
		} else {
			if (!empty($additionalInputConversionFlags)) {
				$ffmpegCommand .= " {$additionalInputConversionFlags}";
			}

			$ffmpegCommand .= " -i {$this->tempSourcePath}";

			if (!empty($additionalOutputConversionFlags)) {
				$ffmpegCommand .= " {$additionalOutputConversionFlags}";
			}
		}

		$ffmpegCommand .= " {$this->tempOutputPath}";

		$commands[] = $ffmpegCommand;

		return implode(' && ', $commands);
	}

	public function handlePostConversion() {
		$this->handlePostConversionSourceFile();
		
		$this->writePostConversionOutputFile();

		return $this;
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

		$newFileName = $this->writeFileSafe($this->outputFolder, $this->tempOutputPath, $this->outputFileName);

		if ($this->postConversionTimestampRule === 'preserveSource') {
			$view = new \OC\Files\View('');
			$newFile = $this->outputFolder->get($newFileName);
			$view->touch($newFile->getPath(), $this->sourceFile->getMTime());
			$newFile->touch($view->filemtime($newFile->getPath()));
		}

		return $this;
	}

	public function unlockConversion() {
		if ($this->parallelConversionEnabled()) {
			return $this;
		}

		$this->setConversionLockActive(false);

		return $this;
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

		return $newFileName;
	}

	protected function parallelConversionEnabled() {
		return $this->configService->getAppConfigValue('convertMediaInParallel') === 'yes';
	}

	protected function setConversionLockActive($state) {
		$lockValue = $state ? time() : null;

		if (empty($this->batchId)) {
			$this->configService->setAppConfigValue('conversionLock', $lockValue);
		} else {
			$this->configService->updateBatch($this->batchId, ['conversion_lock' => $lockValue]);
		}
	}

	protected function conversionLockIsActive() {
		$now = time();

		if (empty($this->batchId)) {
			$lockValue = $this->configService->getAppConfigValue('conversionLock');
			if (empty($lockValue) || $lockValue === 'no') {
				return false;
			}
			$lock = $lockValue;
		} else {
			$batch = $this->configService->getBatch($this->batchId);
			if (empty($batch)) {
				return false;
			}
			if (isset($batch['conversion_lock'])) {
				$lock = $batch['conversion_lock'];
			} else {
				$lock = $now - (31 * 60);
			}
		}

		if (!isset($lock) || empty($lock)) {
			return false;
		}

		$expiration = $lock + (30 * 60);

		if ($now < $expiration) {
			return true;
		}

		$this->setConversionLockActive(false);

		return false;
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

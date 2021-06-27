<?php

namespace OCA\WorkflowMediaConverter\BackgroundJobs;

use OC\Files\Filesystem;
use OC\Files\View;
use OCA\WorkflowMediaConverter\Factory\ViewFactory;
use OCA\WorkflowMediaConverter\Service\ConfigService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ConvertMediaJob extends QueuedJob
{
    private LoggerInterface $logger;
    private IRootFolder $rootFolder;
    private ConfigService $configService;
    private ViewFactory $viewFactory;

    public function __construct(ITimeFactory $time, LoggerInterface $logger, IRootFolder $rootFolder, ConfigService $configService, ViewFactory $viewFactory)
    {
        parent::__construct($time);
        $this->rootFolder = $rootFolder;
        $this->logger = $logger;
        $this->configService = $configService;
        $this->viewFactory = $viewFactory;
    }

    protected function run($arguments)
    {
        $this->logger->info(ConvertMedia::class . ' started');
        try {
            $this
                ->parseArguments($arguments)
                ->convertMedia()
                ->handlePostConversion()
                ->notifyBatchSuccess();
        } catch (\Throwable $e) {
            $this->notifyBatchFail($e);
            $this->logger->error("({$e->getCode()}) :: {$e->getMessage()} :: {$e->getTraceAsString()}");
        } finally {
            $this->logger->info(ConvertMedia::class . ' finished');
        }
    }

    public function parseArguments($arguments)
    {
        $this->configService->setUserId((string)$arguments['user_id']);
        $this->batchId = (string)$arguments['batch_id'];
        $this->path = (string)$arguments['path'];
        $this->postConversionSourceRule = (string)$arguments['postConversionSourceRule'];
        $this->postConversionSourceRuleMoveFolder = (string)$arguments['postConversionSourceRuleMoveFolder'];
        $this->postConversionOutputRule = (string)$arguments['postConversionOutputRule'];
        $this->postConversionOutputRuleMoveFolder = (string)$arguments['postConversionOutputRuleMoveFolder'];
        $this->postConversionOutputConflictRule = (string)$arguments['postConversionOutputConflictRule'];
        $this->postConversionOutputConflictRuleMoveFolder = (string)$arguments['postConversionOutputConflictRuleMoveFolder'];
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
        $this->outputPath = str_replace(".{$this->sourceExtension}", ".{$this->outputExtension}", $this->path);
        $this->outputFileName = basename($this->outputPath);
        $this->outputFolder = $this->postConversionOutputRule === 'move'
            ? $this->rootFolder->get($this->sourceFolder . '/' . $this->postConversionOutputRuleMoveFolder)
            : $this->sourceFile->getParent();

        return $this;
    }

    public function convertMedia()
    {
        $threads = $this->configService->getAppConfigValue('threadLimit', 0);

        $command = "ffmpeg --threads $threads -i {$this->tempSourcePath} {$this->tempOutputPath}";

        $process = new Process($command, null, [], null, null);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $this;
    }

    public function handlePostConversion()
    {        
        $this->handlePostConversionExistingFile();
        $this->handlePostConversionSourceFile();

        return $this;
    }
    
    public function handlePostConversionExistingFile()
    {
        if (!$this->outputFolder->nodeExists($this->outputFileName)) {
            $this->writeFile($this->outputFolder, $this->tempOutputPath, $this->outputFileName);
        }
        
        switch ($this->postConversionOutputConflictRule) {
            case 'move':
                $conflictsFolder = $this->rootFolder->get($this->sourceFolder . '/' . $this->postConversionOutputConflictRuleMoveFolder);
                $this->writeFileSafe($conflictsFolder, $this->outputPath, $this->outputFileName);
                $this->rootFolder->get($this->outputPath)->delete();
                break;
            case 'preserve':
                $this->writeFileSafe($this->outputFolder, $this->tempOutputPath, $this->outputFileName);
                break;
            case 'overwrite':
                $this->rootFolder->get($this->outputPath)->delete();
                $this->writeFile($this->outputFolder, $this->tempOutputPath, $this->outputFileName);
        }
    }
    
    public function handlePostConversionSourceFile()
    {
        switch ($this->postConversionSourceRule) {
            case 'delete':
                $this->sourceFile->delete();
                break;
            case 'move':
                $this->sourceFile->move($this->sourceFolder . '/' . $this->postConversionSourceRuleMoveFolder . '/' . $this->sourceFilename);
                break;
            default:
                break;
        }
    }

    public function notifyBatchSuccess()
    {
        $batch = $this->configService->getBatch($this->batchId);

        $this->configService->updateBatch($this->batchId, [
            'converted' => ($batch['converted'] ?? 0) + 1,
            'status' => ($batch['converted'] + 1) === $batch['unconverted'] ? 'finished' : 'converting'
        ]);
    }

    public function notifyBatchFail(\Throwable $e)
    {
        $batch = $this->configService->getBatch($this->batchId);

        $this->configService->updateBatch($this->batchId, [
            'status' => 'has-failures',
            'failed' => ($batch['failed'] ?? 0) + 1,
            'errors' => array_merge(($batch['errors'] ?? []), ["{$e->getMessage()} -- Error code {$e->getCode()}"])
        ]);
    }

    public function writeFile($folder, $tempFileSource, $filename)
    {
        (new View($folder->getPath()))->fromTmpFile($tempFileSource, $filename);
    }

    public function writeFileSafe($folder, $tempFile, $filename)
    {
        $fileNameNoExtension = str_replace(".{$this->outputExtension}", '', $this->outputFileName);

        $index = 0;

        $newFileName = $filename;

        while ($this->outputFolder->nodeExists($newFileName)) {
            $newFileName = "{$fileNameNoExtension} ({$index}).{$this->outputExtension}";
        }

        $this->writeFile($folder, $tempFile, $newFileName);
    }
}

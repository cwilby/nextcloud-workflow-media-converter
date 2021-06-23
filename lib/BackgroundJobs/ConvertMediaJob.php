<?php

namespace OCA\WorkflowMediaConverter\BackgroundJobs;

use OC\Files\Filesystem;
use OC\Files\View;
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

    public function __construct(ITimeFactory $time, LoggerInterface $logger, IRootFolder $rootFolder, ConfigService $configService)
    {
        parent::__construct($time);
        $this->rootFolder = $rootFolder;
        $this->logger = $logger;
        $this->configService = $configService;
    }

    protected function run($arguments)
    {
        $this->logger->info(ConvertMedia::class . ' started');
        try {
            $this
                ->parseArguments($arguments)
                ->convertMedia()
                ->handlePostConversion();
        } catch (\Throwable $e) {
            $this->logger->error("({$e->getCode()}) :: {$e->getMessage()} :: {$e->getTraceAsString()}");
        } finally {
            $this->logger->info(ConvertMedia::class . ' finished');
        }
    }

    private function parseArguments($arguments)
    {
        $this->path = (string)$arguments['path'];
        $this->postConversionSourceRule = (string)$arguments['postConversionSourceRule'];
        $this->postConversionSourceRuleMoveFolder = (string)$arguments['postConversionSourceRuleMoveFolder'];
        $this->postConversionOutputRule = (string)$arguments['postConversionOutputRule'];
        $this->postConversionOutputRuleMoveFolder = (string)$arguments['postConversionOutputRuleMoveFolder'];
        $this->postConversionOutputConflictRule = (string)$arguments['postConversionOutputConflictRule'];
        $this->postConversionOutputConflictRuleMoveFolder = (string)$arguments['postConversionOutputConflictRuleMoveFolder'];
        $this->outputExtension = (string)$arguments['outputExtension'];

        $this->folder = dirname($this->path);
        $this->filename = basename($this->path);
        $this->sourceExtension = pathinfo($this->filename, PATHINFO_EXTENSION);

        $username = explode('/', $this->path, 4)[1];
        Filesystem::init($username, $this->folder);

        $this->sourceFile = $this->rootFolder->get($this->path);
        $this->folderView = new View($this->folder);

        $this->tempSourcePath = $this->folderView->toTmpFile($this->filename);
        $this->tempSourceFilename = basename($this->tempSourcePath);

        $this->tempOutputPath = str_replace(".{$this->sourceExtension}", ".{$this->outputExtension}", $this->tempSourcePath);
        $this->tempOutputFilename = basename($this->tempOutputPath);

        $this->outputPath = str_replace(".{$this->sourceExtension}", ".{$this->outputExtension}", $this->path);
        $this->outputFileName = basename($this->outputPath);

        $this->outputFolder = $this->postConversionOutputRule === 'move'
            ? $this->rootFolder->get($this->folder . '/' . $this->postConversionOutputRuleMoveFolder)
            : $this->sourceFile->getParent();

        return $this;
    }

    private function convertMedia()
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

    private function handlePostConversion()
    {
        $conflictRule = $this->postConversionOutputConflictRule;

        if ($this->outputFolder->nodeExists($this->outputFileName)) {
            if ($conflictRule === 'move') {
                $this->writeFileSafe(
                    $this->rootFolder->get($this->folder . '/' . $this->postConversionOutputConflictRuleMoveFolder),
                    $this->outputPath,
                    $this->outputFileName
                );
                $this->rootFolder->get($this->outputPath)->delete();
            } else if ($conflictRule === 'preserve') {
                $method = 'writeFileSafe';
            }
        }

        $this->{$method ?? 'writeFile'}($this->outputFolder, $this->tempOutputPath, $this->outputFileName);

        switch ($this->postConversionSourceRule) {
            case 'keep':
                // do nothing
                break;
            case 'delete':
                $this->sourceFile->delete();
                break;
            case 'move':
                $this->sourceFile->move($this->folder . '/' . $this->postConversionSourceRuleMoveFolder . '/' . $this->filename);
                break;
        }

        return $this;
    }

    private function writeFile($folder, $tempFileSource, $filename)
    {
        (new View($folder->getPath()))->fromTmpFile($tempFileSource, $filename);
    }

    private function writeFileSafe($folder, $tempFile, $filename)
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

<?php

namespace OCA\WorkflowMediaConverter\BackgroundJobs;

use OC\Files\Filesystem;
use OC\Files\View;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\ITempManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ConvertMediaJob extends QueuedJob
{
    private ITempManager $tempManager;
    private LoggerInterface $logger;
    private IRootFolder $rootFolder;

    private string $postConversionSourceRule;
    private string $postConversionOutputRule;
    private string $path;
    private string $sourceExtension;
    private string $outputExtension;
    private Node $sourceFile;
    private string $directory;
    private string $filename;
    private string $userFolder;
    private View $directoryView;
    private string $sourcePath;
    private string $outputPath;
    private string $outputFileName;
    private string $tempPath;
    private string $outputTempPath;


    public function __construct(ITimeFactory $time, ITempManager $tempManager, LoggerInterface $logger, IRootFolder $rootFolder)
    {
        parent::__construct($time);
        $this->tempManager = $tempManager;
        $this->rootFolder = $rootFolder;
        $this->logger = $logger;
    }

    protected function run($arguments)
    {
        try {
            $this->logger->info("ConvertMediaJob started");
            $this
                ->parseArguments($arguments)
                ->convertMedia()
                ->handlePostConversion();
            $this->logger->info("ConvertMediaJob finished");
        } catch (\Throwable $e) {
            $this->logger->error("({$e->getCode()}) :: {$e->getMessage()} :: {$e->getTraceAsString()}");
        }
    }

    private function parseArguments($arguments)
    {
        $this->path = (string)$arguments['path'];
        $this->outputExtension = (string)$arguments['outputExtension'];
        $this->postConversionSourceRule = (string)$arguments['postConversionSourceRule'];
        $this->postConversionOutputRule = (string)$arguments['postConversionOutputRule'];

        $pathSegments = explode('/', $this->path, 4);
        $this->userFolder = $pathSegments[1];
        $this->sourceExtension = pathinfo($this->path, PATHINFO_EXTENSION);
        $this->directory = dirname($this->path);
        $this->filename = basename($this->path);

        Filesystem::init($this->userFolder, "/{$this->userFolder}/files");
        $this->sourceFile = $this->rootFolder->get($this->path);
        $this->directoryView = new View($this->directory);
        $this->sourcePath = $this->directoryView->toTmpFile($this->filename);

        $this->outputPath = str_replace($this->sourcePath, ".{$this->sourceExtension}", ".{$this->outputExtension}");
        $this->outputFileName = pathinfo($this->outputPath, PATHINFO_FILENAME);

        $this->tempPath = $this->directoryView->toTmpFile($this->filename);
        $this->outputTempPath = pathinfo($this->tempPath, PATHINFO_FILENAME) . ".$this->outputExtension";

        return $this;
    }

    private function convertMedia()
    {
        $this->logger->info('Calling ffmpeg', [
            'command' => "ffmpeg -i $this->sourcePath $this->outputPath",
        ]);

        $process = new Process(
            "ffmpeg -i $this->sourcePath $this->outputPath",
            null,
            [],
            null,
            null
        );

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $folder = $this->sourceFile->getParent();

        $newFileName = $this->outputFileName;

        if ($this->postConversionOutputRule === 'preserve') {
            $index = 0;
            while ($folder->nodeExists($this->outputFileName)) {
                $newFileName = "$this->outputFileName ($index).$this->outputExtension";
            }
        }

        $this->directoryView->fromTmpFile("$this->tempPath/$this->outputTempPath", $newFileName);

        return $this;
    }

    private function handlePostConversion()
    {
        if ($this->postConversionSourceRule === 'delete') {
            // FIXME: sometimes causes "unable to rename, destination directory is not writable" because the trashbin url
            // looses the user part in \OC\Files\Storage\Local::moveFromStorage() line 460
            // return $rootStorage->rename($sourceStorage->getSourcePath($sourceInternalPath), $this->getSourcePath($targetInternalPath));
            //                                                                                 ^
            $this->sourceFile->delete();
        }

        return $this;
    }
}

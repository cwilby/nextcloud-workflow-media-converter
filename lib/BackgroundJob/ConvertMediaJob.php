<?php

namespace OCA\WorkflowMediaConverter\BackgroundJob;

use OC\Files\Filesystem;
use OC\Files\View;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IConfig;
use OCP\ITempManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ConvertMediaJob extends QueuedJob
{
    private IConfig $config;
    private ITempManager $tempManager;
    private LoggerInterface $logger;
    private IRootFolder $rootFolder;

    private string $originalFileMode;
    private string $targetFileMode;
    private string $path;
    private string $sourceExtension;
    private string $outputExtension;
    private Node $sourceFile;
    private string $directory;
    private string $filename;
    private array $pathSegments;
    private string $userFolder;
    private View $directoryView;
    private string $sourcePath;
    private string $outputPath;
    private string $outputFileName;
    private string $tempPath;
    private string $outputTempPath;


    public function __construct(IConfig $config, ITempManager $tempManager, LoggerInterface $logger, IRootFolder $rootFolder)
    {
        $this->config = $config;
        $this->tempManager = $tempManager;
        $this->rootFolder = $rootFolder;
        $this->logger = $logger;
    }

    protected function run($arguments)
    {
        try {
            $this
                ->parseArguments($arguments)
                ->convertMedia()
                ->handlePostConversion();
        } catch (\Throwable $e) {
            $this->logger->error("({$e->getCode()}) :: $e->getMessage()", ['arguments' => $arguments]);
        }
    }

    private function parseArguments($arguments)
    {
        $this->originalFileMode = (string)$arguments['originalFileMode'];
        $this->targetFileMode = (string)$arguments['targetFileMode'];
        $this->path = (string)$arguments['path'];
        $this->sourceExtension = 'mov';
        $this->outputExtension = 'mp4';

        $this->sourceFile = $this->rootFolder->get($this->path);

        $this->directory = dirname($this->path);
        $this->filename = basename($this->path);
        $this->pathSegments = explode('/', $this->path, 4);;

        $this->userFolder = $this->pathSegments[1];

        Filesystem::init($this->userFolder, "/{$this->userFolder}/files");

        $this->directoryView = new View($this->directory);
        $this->sourcePath = $this->tempManager->getTempBaseDir();
        $this->outputPath = str_replace($this->sourcePath, ".{$this->sourceExtension}", ".{$this->outputExtension}");
        $this->outputFileName = pathinfo($this->outputPath, PATHINFO_FILENAME);

        $this->tempPath = $this->directoryView->toTmpFile($this->filename);
        $this->outputTempPath = pathinfo($this->tempPath, PATHINFO_FILENAME) . ".$this->outputExtension";

        return $this;
    }

    private function convertMedia()
    {
        $process = new Process(
            "ffmpeg -i $this->sourcePath -c $this->outputPath",
            $this->tempPath,
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

        if ($this->targetFileMode === 'preserve') {
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

        if ($this->originalFileMode === 'delete') {
            // FIXME: sometimes causes "unable to rename, destination directory is not writable" because the trashbin url
            // looses the user part in \OC\Files\Storage\Local::moveFromStorage() line 460
            // return $rootStorage->rename($sourceStorage->getSourcePath($sourceInternalPath), $this->getSourcePath($targetInternalPath));
            //                                                                                 ^
            $this->sourceFile->delete();
        }

        return $this;
    }
}

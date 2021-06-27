<?php

namespace OCA\WorkflowMediaConverter\Tests\BackgroundJobs;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use OCA\WorkflowMediaConverter\Service\ConfigService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;

abstract class BackgroundJobTest extends TestCase
{
    protected function setUp(): void
    {
        $this->time = m::spy(ITimeFactory::class);
        $this->logger = m::spy(LoggerInterface::class);
        $this->rootFolder = m::spy(IRootFolder::class);
        $this->jobList = m::spy(IJobList::class);
        $this->configService = m::mock(ConfigService::class);

        $this->videoFolder = $this->createTestFolder();
        $this->videoSubfolder = $this->createTestSubFolder($this->videoFolder);
        $this->videoSubfolderNodes = [
            $this->createFile($this->videoFolder, 'test-1.mov'),
            $this->createFile($this->videoFolder, 'test-2.mov'),
            $this->createFile($this->videoFolder, 'test-2.avi'),
            $this->createFile($this->videoFolder, 'test-3.mov'),
            $this->createFile($this->videoFolder, 'test-3.mp4'),
        ];
        $this->videoSubfolder->shouldReceive('getDirectoryListing')->andReturn($this->videoSubfolderNodes);
        $this->videoFolderNodes = [
            $this->videoSubfolder,
            $this->createFile($this->videoFolder, 'test-1.mov'),
            $this->createFile($this->videoFolder, 'test-2.mov'),
            $this->createFile($this->videoFolder, 'test-2.avi'),
            $this->createFile($this->videoFolder, 'test-3.mov'),
            $this->createFile($this->videoFolder, 'test-3.mp4'),
        ];
        $this->videoFolder->shouldReceive('getDirectoryListing')->andReturn($this->videoFolderNodes);

        $this->audioFolder = $this->createTestFolder();
        $this->audioSubfolder = $this->createTestSubfolder($this->audioFolder);
        $this->audioSubfolderNodes = [
            $this->createFile($this->videoFolder, 'test-1.mov'),
            $this->createFile($this->videoFolder, 'test-2.mov'),
            $this->createFile($this->videoFolder, 'test-2.avi'),
            $this->createFile($this->videoFolder, 'test-3.mov'),
            $this->createFile($this->videoFolder, 'test-3.mp4'),
        ];
        $this->audioSubfolder->shouldReceive('getDirectoryListing')->andReturn($this->audioSubfolderNodes);
        $this->audioFiles = [
            $this->audioSubfolder,
            $this->createFile($this->audioFolder, 'test-1.wav'),
            $this->createFile($this->audioFolder, 'test-2.wav'),
            $this->createFile($this->audioFolder, 'test-2.m4a'),
            $this->createFile($this->audioFolder, 'test-3.wav'),
            $this->createFile($this->audioFolder, 'test-3.mp3'),
        ];
        $this->audioFolder->shouldReceive('createTestFolder')->andReturn($this->audioFiles);
        $this->sourceMoveFolder = $this->createTestFolder();
        $this->outputMoveFolder = $this->createTestFolder();
        $this->conflictMoveFolder = $this->createTestFolder();
    }

    /**
     * @param MockInterface|Folder $folder 
     * @param string $filename 
     * @param string|null $convertedFilename 
     * @return MockInterface|File
     */
    protected function createFile($folder, $filename, $folderPath = '', $convertedFilename = null)
    {
        /** @var MockInterface|File $file */
        $file = m::mock(File::class);

        $file->shouldReceive('getName')->andReturn($filename);
        $file->shouldReceive('getPath')->andReturn("$folderPath/$filename");

        if (!empty($convertedFilename)) {
            $folder->shouldReceive('nodeExists')->with($convertedFilename)->andReturn(false);
        }

        return $file;
    }

    /**
     * 
     * @return MockInterface|Folder
     */
    protected function createTestFolder()
    {
        return m::mock(Folder::class);
    }

    protected function createTestSubfolder($parentFolder)
    {
        $subfolder = $this->createTestFolder();

        $subfolder->shouldReceive('getParent')->andReturn($parentFolder);

        return $subfolder;
    }

    protected abstract function createTestArguments($overrides = []);

    protected function setJobArguments($overrides = [])
    {
        $arguments = $this->createTestArguments($overrides);

        $this->configService->shouldReceive('setUserId')->with($arguments['user_id'])->times(1);

        $this->job->parseArguments($arguments);

        return $arguments;
    }
}

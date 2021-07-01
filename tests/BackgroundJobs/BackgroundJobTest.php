<?php

namespace OCA\WorkflowMediaConverter\Tests\BackgroundJobs;

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use OCA\WorkflowMediaConverter\Service\ConfigService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;

abstract class BackgroundJobTest extends MockeryTestCase
{
    protected function setUp(): void
    {
        $this->time = m::mock(ITimeFactory::class);
        $this->logger = m::spy(LoggerInterface::class);
        $this->rootFolder = m::mock(IRootFolder::class);
        $this->jobList = m::mock(IJobList::class);
        $this->configService = m::mock(ConfigService::class);

        $this->videoFolder = $this->createTestFolder('/admin/files/camera-uploads');
        $this->videoSubfolder = $this->createTestSubFolder($this->videoFolder, '/admin/files/camera-uploads/2020');
        $this->videoSubfolderNodes = [
            $this->createFile($this->videoFolder, 'test-1.mov', '/admin/files/camera-uploads'),
            $this->createFile($this->videoFolder, 'test-2.mov', '/admin/files/camera-uploads'),
            $this->createFile($this->videoFolder, 'test-2.avi', '/admin/files/camera-uploads'),
            $this->createFile($this->videoFolder, 'test-3.mov', '/admin/files/camera-uploads'),
            $this->createFile($this->videoFolder, 'test-3.mp4', '/admin/files/camera-uploads'),
        ];
        $this->videoSubfolder->allows()->getDirectoryListing()->andReturns($this->videoSubfolderNodes);
        $this->videoFolderNodes = [
            $this->videoSubfolder,
            $this->createFile($this->videoFolder, 'test-1.mov', '/admin/files/camera-uploads/2020'),
            $this->createFile($this->videoFolder, 'test-2.mov', '/admin/files/camera-uploads/2020'),
            $this->createFile($this->videoFolder, 'test-2.avi', '/admin/files/camera-uploads/2020'),
            $this->createFile($this->videoFolder, 'test-3.mov', '/admin/files/camera-uploads/2020'),
            $this->createFile($this->videoFolder, 'test-3.mp4', '/admin/files/camera-uploads/2020'),
        ];
        $this->videoFolder->allows()->getDirectoryListing()->andReturns($this->videoFolderNodes);

        $this->sourceMoveFolder = $this->createTestFolder('/admin/files/converted/source');
        $this->outputMoveFolder = $this->createTestFolder('/admin/files/converted/output');
        $this->conflictMoveFolder = $this->createTestFolder('/admin/files/converted/conflicts');
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

        $file->allows()->getName()->andReturns($filename);
        $file->allows()->getPath()->andReturns("$folderPath/$filename");
        $file->allows()->getParent()->andReturns($folder);

        if (!empty($convertedFilename)) {
            $folder->allows()->nodeExists($convertedFilename)->andReturns(false);
        }

        return $file;
    }

    /**
     * 
     * @return MockInterface|Folder
     */
    protected function createTestFolder($path)
    {
        $folder = m::mock(Folder::class);
        $folder->allows()->getPath()->andReturns($path);

        $this->rootFolder->allows()->get($path)->andReturns($folder);

        return $folder;
    }

    protected function createTestSubfolder($parentFolder, $path)
    {
        $subfolder = $this->createTestFolder($path);

        $subfolder->allows()->getParent()->andReturns($parentFolder);
        $this->rootFolder->allows()->get($path)->andReturns($subfolder);

        return $subfolder;
    }

    protected abstract function createJobArguments($overrides = []);

    protected function setJobArguments($overrides = [])
    {
        $arguments = $this->createJobArguments($overrides);

        $this->configService->expects()->setUserId($arguments['user_id'])->once();

        $this->job->parseArguments($arguments);

        return $arguments;
    }
}

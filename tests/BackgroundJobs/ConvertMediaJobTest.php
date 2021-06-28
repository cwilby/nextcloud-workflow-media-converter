<?php

namespace OCA\WorkflowMediaConverter\Tests\BackgroundJobs;

use OCA\WorkflowMediaConverter\BackgroundJobs\ConvertMediaJob;
use Mockery as m;
use OC\Files\View;
use OCA\WorkflowMediaConverter\Factory\ProcessFactory;
use OCA\WorkflowMediaConverter\Factory\ViewFactory;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ConvertMediaJobTest extends BackgroundJobTest
{
    protected ConvertMediaJob $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->view = m::mock(View::class);
        $this->viewFactory = m::mock(ViewFactory::class);

        $this->process = m::mock(Process::class);
        $this->processFactory = m::mock(ProcessFactory::class);

        $this->job = new ConvertMediaJob(
            $this->time,
            $this->logger,
            $this->rootFolder,
            $this->configService,
            $this->viewFactory,
            $this->processFactory
        );
    }

    protected function tearDown(): void
    {
        \Mockery::close();
    }

    public function test_ParseArguments()
    {
        $arguments = $this->createJobArguments();

        $this->viewFactory->allows()->create(dirname($arguments['path']))->andReturns($this->view);
        $this->view->allows()->toTmpFile(basename($arguments['path']))->andReturns('/tmp/random-filename-for-test-1.mov');

        $this->rootFolder->expects()->get($arguments['path'])->andReturns($this->videoFolderNodes[0]);

        $this->configService->allows()->setUserId($arguments['user_id']);

        $result = $this->job->parseArguments($arguments);

        $this->assertEquals($this->job, $result);
    }

    public function test_ParseArguments_GivenMoveOutputFolder()
    {
        $arguments = $this->createJobArguments([
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/videos/output'
        ]);

        $this->viewFactory->allows()->create(dirname($arguments['path']))->andReturns($this->view);
        $this->view->allows()->toTmpFile(basename($arguments['path']))->andReturns('/tmp/random-filename-for-test-1.mov');

        $this->rootFolder->expects()->get($arguments['path'])->andReturns($this->videoFolderNodes[0]);
        $this->rootFolder->expects()->get($arguments['postConversionOutputRuleMoveFolder'])->andReturns($this->outputMoveFolder);

        $this->configService->allows()->setUserId($arguments['user_id']);

        $result = $this->job->parseArguments($arguments);

        $this->assertEquals($this->job, $result);
    }

    public function test_ConvertMedia_GivenDefaultArguments()
    {
        $arguments = $this->createJobArguments();

        $this->viewFactory->allows()->create(dirname($arguments['path']))->andReturns($this->view);
        $this->view->allows()->toTmpFile(basename($arguments['path']))->andReturns('/tmp/random-filename-for-test-1.mov');

        $this->configService->allows()->getAppConfigValue('threadLimit', 0)->andReturns(0);
        $this->rootFolder->allows()->get($arguments['path'])->andReturns($this->videoFolderNodes[0]);

        $this->processFactory->expects()->create("ffmpeg --threads 0 -i /tmp/random-filename-for-test-1.mov /tmp/random-filename-for-test-1.mp4")->andReturns($this->process);
        $this->process->expects()->run();
        $this->process->expects()->isSuccessful()->andReturns(true);

        $this->setJobArguments($arguments);

        $result = $this->job->convertMedia();

        $this->assertEquals($this->job, $result);
    }

    public function test_ConvertMedia_GivenFFmpegFailure()
    {
        $arguments = $this->createJobArguments();

        $this->viewFactory->allows()->create(dirname($arguments['path']))->andReturns($this->view);
        $this->view->allows()->toTmpFile(basename($arguments['path']))->andReturns('/tmp/random-filename-for-test-1.mov');

        $this->configService->allows()->getAppConfigValue('threadLimit', 0)->andReturns(0);
        $this->rootFolder->allows()->get($arguments['path'])->andReturns($this->videoFolderNodes[0]);

        $this->processFactory->expects()->create("ffmpeg --threads 0 -i /tmp/random-filename-for-test-1.mov /tmp/random-filename-for-test-1.mp4")->andReturns($this->process);
        $this->process->expects()->run();
        $this->process->expects()->isSuccessful()->andReturns(false);
        $this->process->expects()->getCommandLine();
        $this->process->expects()->getExitCode();
        $this->process->expects()->getExitCodeText();
        $this->process->expects()->getWorkingFolder();
        $this->process->expects()->isOutputDisabled();
        $this->process->expects()->getOutput();
        $this->process->expects()->getErrorOutput();

        $this->expectException(ProcessFailedException::class);

        $this->setJobArguments($arguments);

        $result = $this->job->convertMedia();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictKeepKeepPreserve_NoConflict_ShouldStoreOutputInSourceFolder()
    {
        $this->rootFolder->expects()->get('/admin/files/camera-uploads/test-1.mov')->andReturns($this->videoFolderNodes[0]);
        $this->videoFolder->expects()->nodeExists('test-1.mp4')->andReturns(false);
        $this->viewFactory->expects()->create('/admin/files/camera-uploads')->andReturns($this->view)->twice();
        $this->view->expects()->toTmpFile('test-1.mov')->andReturns('/tmp/random-filename-for-test-1.mov');
        $this->view->expects()->fromTmpFile('/tmp/random-filename-for-test-1.mp4', 'test-1.mp4');

        $this->setJobArguments([
            'postConversionSourceRule' => 'keep',
            'postConversionOutputRule' => 'keep',
            'postConversionOutputConflictRule' => 'preserve'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictKeepKeepPreserve_WhenConflict_ShouldStoreOutputInSourceFolderWithNewFilename()
    {
        $this->rootFolder->expects()->get('/admin/files/camera-uploads/test-1.mov')->andReturns($this->videoFolderNodes[0]);
        $this->videoFolder->expects()->nodeExists('test-1.mp4')->andReturns(true)->twice();
        $this->videoFolder->expects()->nodeExists('test-1 (1).mp4')->andReturns(false);
        $this->viewFactory->expects()->create('/admin/files/camera-uploads')->andReturns($this->view)->twice();
        $this->view->expects()->toTmpFile('test-1.mov')->andReturns('/tmp/random-filename-for-test-1.mov');
        $this->view->expects()->fromTmpFile('/tmp/random-filename-for-test-1.mp4', 'test-1 (1).mp4');

        $this->setJobArguments([
            'postConversionSourceRule' => 'keep',
            'postConversionOutputRule' => 'keep',
            'postConversionOutputConflictRule' => 'preserve'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictKeepKeepOverwrite_NoConflict_ShouldStoreOutputInSourceFolder()
    {
        $this->rootFolder->expects()->get('/admin/files/camera-uploads/test-1.mov')->andReturns($this->videoFolderNodes[0]);
        $this->videoFolder->expects()->nodeExists('test-1.mp4')->andReturns(false)->twice();
        $this->viewFactory->expects()->create('/admin/files/camera-uploads')->andReturns($this->view)->twice();
        $this->view->expects()->toTmpFile('test-1.mov')->andReturns('/tmp/random-filename-for-test-1.mov');
        $this->view->expects()->fromTmpFile('/tmp/random-filename-for-test-1.mp4', 'test-1.mp4');

        $this->setJobArguments([
            'postConversionSourceRule' => 'keep',
            'postConversionOutputRule' => 'keep',
            'postConversionOutputConflictRule' => 'overwrite'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictKeepKeepOverwrite_WhenConflict_ShouldOverwriteOutputInSourceFolder()
    {
        $sourceFile = $this->videoFolderNodes[3];
        $existingFile = $this->videoFolderNodes[4];
        $this->rootFolder->expects()->get('/admin/files/camera-uploads/test-3.mov')->andReturns($sourceFile);
        $this->rootFolder->expects()->get('/admin/files/camera-uploads/test-3.mp4')->andReturns($existingFile);
        $this->videoFolder->expects()->nodeExists('test-3.mp4')->andReturns(true, false)->twice();
        $existingFile->expects()->delete()->once();
        $this->viewFactory->expects()->create('/admin/files/camera-uploads')->andReturns($this->view)->twice();
        $this->view->expects()->toTmpFile('test-3.mov')->andReturns('/tmp/random-filename-for-test-3.mov');
        $this->view->expects()->fromTmpFile('/tmp/random-filename-for-test-3.mp4', 'test-3.mp4');


        $this->setJobArguments([
            'path' => '/admin/files/camera-uploads/test-3.mov',
            'postConversionSourceRule' => 'keep',
            'postConversionOutputRule' => 'keep',
            'postConversionOutputConflictRule' => 'overwrite'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictKeepKeepMove_NoConflict_ShouldStoreOutputInSourceFolder()
    {
        $this->viewFactory->expects()->create('/admin/files/camera-uploads')->andReturns($this->view)->twice();
        $this->view->expects()->toTmpFile('test-1.mov')->andReturns('/tmp/random-filename-for-test-1.mov');
        $this->view->expects()->fromTmpFile('/tmp/random-filename-for-test-1.mp4', 'test-1.mp4');
        $this->rootFolder->expects()->get('/admin/files/camera-uploads/test-1.mov')->andReturns($this->videoFolderNodes[0]);
        $this->videoFolder->expects()->nodeExists('test-1.mp4')->andReturns(false)->twice();

        $this->setJobArguments([
            'postConversionSourceRule' => 'keep',
            'postConversionOutputRule' => 'keep',
            'postConversionOutputConflictRule' => 'move',
            'postConversionOutputConflictRuleMoveFolder' => 'converted/conflicts'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }
    public function test_HandlePostConversion_GivenSourceOutputConflictKeepKeepMove_WhenConflict_ShouldStoreOutputInSourceFolder()
    {
        $sourceFile = $this->videoFolderNodes[3];
        $existingFile = $this->videoFolderNodes[4];
        $this->rootFolder->expects()->get('/admin/files/camera-uploads/test-3.mov')->andReturns($sourceFile);
        $this->rootFolder->expects()->get('/admin/files/camera-uploads/test-3.mp4')->andReturns($existingFile);
        $this->videoFolder->expects()->nodeExists('test-3.mp4')->andReturns(true, false)->twice();
        $existingFile->expects()->move('/admin/files/converted/conflicts/test-3.mp4');
        $this->viewFactory->expects()->create('/admin/files/camera-uploads')->andReturns($this->view)->twice();
        $this->view->expects()->toTmpFile('test-3.mov')->andReturns('/tmp/random-filename-for-test-3.mov');
        $this->view->expects()->fromTmpFile('/tmp/random-filename-for-test-3.mp4', 'test-3.mp4');

        $this->setJobArguments([
            'path' => '/admin/files/camera-uploads/test-3.mov',
            'postConversionSourceRule' => 'keep',
            'postConversionOutputRule' => 'keep',
            'postConversionOutputConflictRule' => 'move',
            'postConversionOutputConflictRuleMoveFolder' => '/converted/conflicts'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictKeepMovePreserve_NoConflict_ShouldMoveOutputToOutputFolder()
    {
        $this->rootFolder->expects()->get('/admin/files/camera-uploads/test-1.mov')->andReturns($this->videoFolderNodes[0]);
        $this->outputMoveFolder->expects()->nodeExists('test-1.mp4')->andReturns(false)->twice();
        $this->viewFactory->expects()->create('/admin/files/camera-uploads')->andReturns($this->view)->once();
        $this->outputView = m::mock(View::class);
        $this->viewFactory->expects()->create('/admin/files/converted/output')->andReturns($this->outputView)->once();
        $this->view->expects()->toTmpFile('test-1.mov')->andReturns('/tmp/random-filename-for-test-1.mov');
        $this->outputView->expects()->fromTmpFile('/tmp/random-filename-for-test-1.mp4', 'test-1.mp4');

        $this->setJobArguments([
            'postConversionSourceRule' => 'keep',
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/converted/output',
            'postConversionOutputConflictRule' => 'preserve'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }
    public function test_HandlePostConversion_GivenSourceOutputConflictKeepMovePreserve_WhenConflict_ShouldMoveOutputToOutputFolderWithNewFileName()
    {
        $sourceFile = $this->videoFolderNodes[3];
        $existingFile = $this->videoFolderNodes[4];
        $this->rootFolder->expects()->get('/admin/files/camera-uploads/test-3.mov')->andReturns($sourceFile);
        $this->rootFolder->expects()->get('/admin/files/camera-uploads/test-3.mp4')->andReturns($existingFile);
        $this->outputMoveFolder->expects()->nodeExists('test-3.mp4')->andReturns(true)->twice();
        $this->outputMoveFolder->expects()->nodeExists('test-3 (1).mp4')->andReturns(false)->once();

        $this->viewFactory->expects()->create('/admin/files/camera-uploads')->andReturns($this->view)->once();
        $this->outputView = m::mock(View::class);
        $this->viewFactory->expects()->create('/admin/files/converted/output')->andReturns($this->outputView)->once();
        $this->view->expects()->toTmpFile('test-3.mov')->andReturns('/tmp/random-filename-for-test-3.mov');
        $this->outputView->expects()->fromTmpFile('/tmp/random-filename-for-test-3.mp4', 'test-3 (1).mp4');

        $this->setJobArguments([
            'path' => '/admin/files/camera-uploads/test-3.mov',
            'postConversionSourceRule' => 'keep',
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/converted/output',
            'postConversionOutputConflictRule' => 'preserve'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictKeepMoveOverwrite_WhenOutputNotInFolder()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'keep',
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/converted/output',
            'postConversionOutputConflictRule' => 'overwrite'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }
    public function test_HandlePostConversion_GivenSourceOutputConflictKeepMoveOverwrite_WhenOutputInFolder()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'keep',
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/converted/output',
            'postConversionOutputConflictRule' => 'overwrite'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictKeepMoveMove_WhenOutputNotInFolderAndConflictNotInFolder_ShouldStoreInOutputFolder()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'keep',
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/converted/output',
            'postConversionOutputConflictRule' => 'move',
            'postConversionOutputConflictRuleMoveFolder' => '/admin/files/converted/conflicts'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }
    public function test_HandlePostConversion_GivenSourceOutputConflictKeepMoveMove_WhenOutputNotInFolderAndConflictInFolder_ShouldStoreInOutputFolder()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'keep',
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/converted/output',
            'postConversionOutputConflictRule' => 'move',
            'postConversionOutputConflictRuleMoveFolder' => '/admin/files/converted/conflicts'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }
    public function test_HandlePostConversion_GivenSourceOutputConflictKeepMoveMove_WhenOutputInFolderAndConflictNotInFolder_ShouldMoveExistingOutputToConflict()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'keep',
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/converted/output',
            'postConversionOutputConflictRule' => 'move',
            'postConversionOutputConflictRuleMoveFolder' => '/admin/files/converted/conflicts'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }
    public function test_HandlePostConversion_GivenSourceOutputConflictKeepMoveMove_WhenOutputInFolderAndConflictInFolder_ShouldMoveExistingOutputToConflictWithNewName()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'keep',
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/converted/output',
            'postConversionOutputConflictRule' => 'move',
            'postConversionOutputConflictRuleMoveFolder' => '/admin/files/converted/conflicts'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictDeleteKeepPreserve_WhenNoConflict_ShouldAddFile()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'delete',
            'postConversionOutputRule' => 'keep',
            'postConversionOutputConflictRule' => 'preserve'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictDeleteKeepPreserve_WhenConflict_ShouldAddNewFile()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'delete',
            'postConversionOutputRule' => 'keep',
            'postConversionOutputConflictRule' => 'preserve'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictDeleteKeepOverwrite_NoConflict_ShouldNotOverwrite()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'delete',
            'postConversionOutputRule' => 'keep',
            'postConversionOutputConflictRule' => 'overwrite',
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictDeleteKeepMove_NoConflict_ShouldStoreOutputInSourceFolder()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'delete',
            'postConversionOutputRule' => 'keep',
            'postConversionOutputConflictRule' => 'move',
            'postConversionOutputConflictRuleMoveFolder' => '/admin/files/converted/conflicts'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictDeleteKeepMove_WhenConflict_ShouldMoveExistingOutputToConflictAndStoreOutputInSourceFolder()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'delete',
            'postConversionOutputRule' => 'keep',
            'postConversionOutputConflictRule' => 'move',
            'postConversionOutputConflictRuleMoveFolder' => '/admin/files/converted/conflicts'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictDeleteMovePreserve_NoConflict_ShouldStoreOutputInOutputFolder()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'delete',
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/converted/output',
            'postConversionOutputConflictRule' => 'preserve',
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictDeleteMovePreserve_WhenConflict_ShouldStoreOutputInOutputFolderWithNewName()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'delete',
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/converted/output',
            'postConversionOutputConflictRule' => 'preserve',
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictDeleteMoveOverwrite_NoConflict_ShouldMoveOutputToOutputFolder()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'delete',
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/converted/output',
            'postConversionOutputConflictRule' => 'overwrite',
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictDeleteMoveOverwrite_WhenConflict_ShouldOverwriteOutputInOutputFolder()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'delete',
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/converted/output',
            'postConversionOutputConflictRule' => 'overwrite',
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictDeleteMoveMove_NoConflict_ShouldStoreOutputInOutputFolder()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'delete',
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/converted/output',
            'postConversionOutputConflictRule' => 'move',
            'postConversionOutputConflictRuleMoveFolder' => '/admin/files/converted/conflicts'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictDeleteMoveMove_WhenConflict_ShouldMoveExistingOutputAndStoreOutputInOutputFolder()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'delete',
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/converted/output',
            'postConversionOutputConflictRule' => 'move',
            'postConversionOutputConflictRuleMoveFolder' => '/admin/files/converted/conflicts'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictMoveKeepKeep()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'move',
            'postConversionSourceRuleMoveFolder' => '/admin/files/converted/source',
            'postConversionOutputRule' => 'keep',
            'postConversionOutputConflictRule' => 'preserve',
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictMoveKeepOverwrite()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'move',
            'postConversionSourceRuleMoveFolder' => '/admin/files/converted/source',
            'postConversionOutputRule' => 'keep',
            'postConversionOutputConflictRule' => 'overwrite',
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictMoveKeepMove()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'move',
            'postConversionSourceRuleMoveFolder' => '/admin/files/converted/source',
            'postConversionOutputRule' => 'keep',
            'postConversionOutputConflictRule' => 'move',
            'postConversionOutputConflictRuleMoveFolder' => '/admin/files/converted/conflicts'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictMoveMoveKeep()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'move',
            'postConversionSourceRuleMoveFolder' => '/admin/files/converted/source',
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/converted/output',
            'postConversionOutputConflictRule' => 'preserve'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictMoveMoveOverwrite()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'move',
            'postConversionSourceRuleMoveFolder' => '/admin/files/converted/source',
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/converted/output',
            'postConversionOutputConflictRule' => 'overwrite'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_HandlePostConversion_GivenSourceOutputConflictMoveMoveMove()
    {
        $this->setJobArguments([
            'postConversionSourceRule' => 'move',
            'postConversionSourceRuleMoveFolder' => '/admin/files/converted/source',
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/admin/files/converted/output',
            'postConversionOutputConflictRule' => 'move',
            'postConversionOutputConflictRuleMoveFolder' => '/admin/files/converted/conflicts'
        ]);

        $result = $this->job->handlePostConversion();

        $this->assertEquals($this->job, $result);
    }

    protected function createJobArguments($overrides = [])
    {
        return array_merge([
            'user_id' => 'admin',
            'batch_id' => 'rjmoalgbvoekv4yy11ijegpjpnk90gmv',
            'path' => '/admin/files/camera-uploads/test-1.mov',
            'outputExtension' => 'mp4',
            'postConversionSourceRule' => 'keep',
            'postConversionSourceRuleMoveFolder' => '/admin/files/videos/sources',
            'postConversionOutputRule' => 'keep',
            'postConversionOutputRuleMoveFolder' => '/admin/files/videos/converted',
            'postConversionOutputConflictRule' => 'preserve',
            'postConversionOutputConflictRuleMoveFolder' => '/admin/files/videos/conflicts',
        ], $overrides);
    }

    protected function setJobArguments($overrides = [])
    {
        return parent::setJobArguments($overrides);
    }
}

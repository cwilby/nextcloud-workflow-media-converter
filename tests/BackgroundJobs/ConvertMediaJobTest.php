<?php

namespace OCA\WorkflowMediaConverter\Tests\BackgroundJobs;

use OCA\WorkflowMediaConverter\BackgroundJobs\ConvertMediaJob;
use Mockery as m;
use OC\Files\View;
use OCA\WorkflowMediaConverter\Factory\ProcessFactory;
use OCA\WorkflowMediaConverter\Factory\ViewFactory;
use OCP\Files\File;
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
            'postConversionOutputRuleMoveFolder' => '/files/admin/videos/output'
        ]);

        $this->viewFactory->allows()->create(dirname($arguments['path']))->andReturns($this->view);
        $this->view->allows()->toTmpFile(basename($arguments['path']))->andReturns('/tmp/random-filename-for-test-1.mov');

        $this->rootFolder->expects()->get($arguments['path'])->andReturns($this->videoFolderNodes[0]);
        $this->rootFolder->expects()->get($arguments['postConversionOutputRuleMoveFolder'])->andReturns($this->outputMoveFolder);

        $this->configService->allows()->setUserId($arguments['user_id']);

        $result = $this->job->parseArguments($arguments);

        $this->assertEquals($this->job, $result);
    }

    public function test_ConvertMedia_GivenDefaultRuleset()
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

    protected function createJobArguments($overrides = [])
    {
        return array_merge([
            'user_id' => 'admin',
            'batch_id' => 'rjmoalgbvoekv4yy11ijegpjpnk90gmv',
            'path' => '/files/admin/camera-uploads/test-1.mov',
            'outputExtension' => 'mp4',
            'postConversionSourceRule' => 'keep',
            'postConversionSourceRuleMoveFolder' => '/files/admin/videos/sources',
            'postConversionOutputRule' => 'keep',
            'postConversionOutputRuleMoveFolder' => '/files/admin/videos/converted',
            'postConversionOutputConflictRule' => 'preserve',
            'postConversionOutputConflictRuleMoveFolder' => '/files/admin/videos/conflicts',
        ], $overrides);
    }

    protected function setJobArguments($overrides = [])
    {
        return parent::setJobArguments($overrides);
    }
}

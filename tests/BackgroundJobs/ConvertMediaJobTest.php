<?php

namespace OCA\WorkflowMediaConverter\Tests\BackgroundJobs;

use OCA\WorkflowMediaConverter\BackgroundJobs\ConvertMediaJob;
use Mockery as m;
use OC\Files\View;
use OCA\WorkflowMediaConverter\Factory\ViewFactory;
use OCP\Files\File;

class ConvertMediaJobTest extends BackgroundJobTest
{
    protected ConvertMediaJob $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->view = m::mock(View::class);
        $this->viewFactory = m::mock(ViewFactory::class);

        $this->job = new ConvertMediaJob(
            $this->time,
            $this->logger,
            $this->rootFolder,
            $this->configService,
            $this->viewFactory
        );
    }

    public function test_ParseArguments()
    {
        $arguments = $this->createTestArguments();

        $this->viewFactory->allows()->create(dirname($arguments['path']))->andReturns($this->view);
        $this->view->allows()->toTmpFile(basename($arguments['path']))->andReturns('/tmp/random-filename-for-test-1.mov');

        $this->test1File = m::mock(File::class);
        $this->test1File->expects()->getParent()->andReturns($this->videoFolder);

        $this->rootFolder->expects()->get($arguments['path'])->andReturns($this->test1File);

        $this->configService->allows()->setUserId($arguments['user_id']);

        $result = $this->job->parseArguments($arguments);

        $this->assertEquals($this->job, $result);
    }

    public function test_ParseArguments_GivenMoveOutputFolder()
    {
        $arguments = $this->createTestArguments([
            'postConversionOutputRule' => 'move',
            'postConversionOutputRuleMoveFolder' => '/files/admin/videos/output'
        ]);

        $this->viewFactory->allows()->create(dirname($arguments['path']))->andReturns($this->view);
        $this->view->allows()->toTmpFile(basename($arguments['path']))->andReturns('/tmp/random-filename-for-test-1.mov');

        $this->test1File = m::mock(File::class);
        $this->test1File->expects()->getParent()->andReturns($this->videoFolder);

        $this->rootFolder->expects()->get($arguments['path'])->andReturns($this->test1File);

        $this->configService->allows()->setUserId($arguments['user_id']);

        $result = $this->job->parseArguments($arguments);

        $this->assertEquals($this->job, $result);
    }


    protected function createTestArguments($overrides = [])
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
}

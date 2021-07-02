<?php

namespace OCA\WorkflowMediaConverter\Tests\BackgroundJobs;

use Mockery\MockInterface;
use OCA\WorkflowMediaConverter\BackgroundJobs\BatchConvertMediaJob;
use OCA\WorkflowMediaConverter\BackgroundJobs\ConvertMediaJob;
use stdClass;

class BatchConvertMediaJobTest extends BackgroundJobTest
{
    /** @var BatchConvertMediaJob */
    protected $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configService->allows()->setBatchStatus(\Mockery::any(), 'seeking');
        $this->configService->allows()->setBatchStatus(\Mockery::any(), 'converting');

        $this->job = new BatchConvertMediaJob(
            $this->time,
            $this->logger,
            $this->rootFolder,
            $this->jobList,
            $this->configService
        );
    }

    public function test_ParseArguments()
    {
        $arguments = $this->createJobArguments();

        $this->configService->expects()->setUserId($arguments['uid'] ?? $arguments['user_id']);

        $result = $this->job->parseArguments($arguments);

        $this->assertEquals($arguments['uid'], $this->job->userId);
        $this->assertEquals($this->job, $result);
        $this->assertEquals($this->videoFolder, $result->sourceFolder);
    }

    public function test_FindUnconvertedMediaInFolder_WhenSubfoldersOptionYes_ShouldFindFilesInSubFolders()
    {
        $this->setJobArguments([
            'convertMediaInSubFolders' => true
        ]);

        $this->videoFolder->allows()->nodeExists('test-1.mp4')->andReturns(false);
        $this->videoFolder->allows()->nodeExists('test-2.mp4')->andReturns(false);
        $this->videoFolder->allows()->nodeExists('test-3.mp4')->andReturns(true);
        $this->videoSubfolder->allows()->nodeExists('test-1.mp4')->andReturns(false);
        $this->videoSubfolder->allows()->nodeExists('test-2.mp4')->andReturns(false);
        $this->videoSubfolder->allows()->nodeExists('test-3.mp4')->andReturns(true);

        $result = $this->job->findUnconvertedMediaInFolder($this->videoFolder);

        $this->assertEquals($this->job, $result);
        $this->assertCount(4, $this->job->unconvertedMedia);
    }

    public function test_FindUnconvertedMediaInFolder_WhenSubfoldersOptionNo_ShouldOnlyFindFilesInSourceFolder()
    {
        $this->setJobArguments([
            'convertMediaInSubFolders' => false
        ]);

        $this->videoFolder->allows()->nodeExists('test-1.mp4')->andReturns(false);
        $this->videoFolder->allows()->nodeExists('test-2.mp4')->andReturns(false);
        $this->videoFolder->allows()->nodeExists('test-3.mp4')->andReturns(true);
        $this->videoSubfolder->allows()->nodeExists('test-1.mp4')->andReturns(false);
        $this->videoSubfolder->allows()->nodeExists('test-2.mp4')->andReturns(false);
        $this->videoSubfolder->allows()->nodeExists('test-3.mp4')->andReturns(true);

        $result = $this->job->findUnconvertedMediaInFolder($this->videoFolder);

        $this->assertEquals($this->job, $result);
        $this->assertCount(2, $this->job->unconvertedMedia);
    }

    public function test_QueueUnconvertedMediaForConversion_QueuesConvertJobs()
    {
        $folder = $this->createTestFolder('/admin/files/camera-uploads');

        $arguments = $this->setJobArguments();


        $unconvertedMedia = [
            ['path' => '/admin/files/camera-uploads/test-1.mov', 'node' => $this->createFile($folder, 'test-1.mov', '/admin/files/camera-uploads')],
            ['path' => '/admin/files/camera-uploads/test-2.mov', 'node' => $this->createFile($folder, 'test-2.mov', '/admin/files/camera-uploads')],
        ];

        $this->job->unconvertedMedia = array_map(function ($x) {
            return $x['node'];
        }, $unconvertedMedia);

        $this->configService->expects()->updateBatch($this->job->batchId, ['unconverted' => 2]);

        foreach ($unconvertedMedia as $media) {
            $this->jobList->expects('add')->with(ConvertMediaJob::class, [
                'uid'                                        => $arguments['uid'],
                'batch_id'                                   => $arguments['id'],
                'path'                                       => $media['path'],
                'outputExtension'                            => $arguments['outputExtension'],
                'postConversionSourceRule'                   => $arguments['postConversionSourceRule'],
                'postConversionSourceRuleMoveFolder'         => null,
                'postConversionOutputRule'                   => $arguments['postConversionOutputRule'],
                'postConversionOutputRuleMoveFolder'         => null,
                'postConversionOutputConflictRule'           => $arguments['postConversionOutputConflictRule'],
                'postConversionOutputConflictRuleMoveFolder' => null
            ]);
        }

        $result = $this->job->queueUnconvertedMediaForConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_Run_ShouldBatchConvertMedia()
    {
        $arguments = $this->createJobArguments();

        $this->configService->allows()->setUserId($arguments['uid']);

        $this->videoFolder->allows()->nodeExists('test-1.mp4')->andReturns(false);
        $this->videoFolder->allows()->nodeExists('test-2.mp4')->andReturns(false);
        $this->videoFolder->allows()->nodeExists('test-3.mp4')->andReturns(true);
        $this->videoSubfolder->allows()->nodeExists('test-1.mp4')->andReturns(false);
        $this->videoSubfolder->allows()->nodeExists('test-2.mp4')->andReturns(false);
        $this->videoSubfolder->allows()->nodeExists('test-3.mp4')->andReturns(true);

        $this->configService->expects()->updateBatch($arguments['id'], [
            'unconverted' => 4
        ]);

        $this->jobList->expects()->add(ConvertMediaJob::class, \Mockery::any())->times(4);

        $this->job->run($arguments);

        $this->assertTrue(true);
    }

    public function test_Run_ShouldHandleBatchFailures()
    {
        $arguments = $this->createJobArguments();

        $this->configService->allows()->setUserId($arguments['uid']);

        $this->videoFolder->allows()->nodeExists('test-1.mp4')->andThrows(new \Exception());
        $this->videoFolder->allows()->nodeExists('test-2.mp4')->andReturns(false);
        $this->videoFolder->allows()->nodeExists('test-3.mp4')->andReturns(true);
        $this->videoSubfolder->allows()->nodeExists('test-1.mp4')->andReturns(false);
        $this->videoSubfolder->allows()->nodeExists('test-2.mp4')->andReturns(false);
        $this->videoSubfolder->allows()->nodeExists('test-3.mp4')->andReturns(true);

        $this->configService->expects()->setBatchStatus($arguments['id'], 'failed');

        $this->jobList->expects()->add(ConvertMediaJob::class, \Mockery::any())->times(0);

        $this->job->run($arguments);

        $this->assertTrue(true);
    }

    protected function createJobArguments($overrides = [])
    {
        return array_merge([
            'uid' => 'admin',
            'id' => 'rjmoalgbvoekv4yy11ijegpjpnk90gmv',
            'status' => 'queued',
            'sourceFolder' => '/camera-uploads',
            'convertMediaInSubFolders' => true,
            'sourceExtension' => 'mov',
            'outputExtension' => 'mp4',
            'postConversionSourceRule' => 'keep',
            'postConversionSourceRuleMoveFolder' => null,
            'postConversionOutputRule' => 'keep',
            'postConversionOutputRuleMoveFolder' => null,
            'postConversionOutputConflictRule' => 'preserve',
            'postConversionOutputConflictRuleMoveFolder' => null
        ], $overrides);
    }

    protected function setJobArguments($overrides = [])
    {
        $arguments = parent::setJobArguments($overrides);

        return $arguments;
    }
}

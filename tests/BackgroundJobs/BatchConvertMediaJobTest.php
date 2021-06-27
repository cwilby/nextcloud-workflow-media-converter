<?php

namespace OCA\WorkflowMediaConverter\Tests\BackgroundJobs;

use OCA\WorkflowMediaConverter\BackgroundJobs\BatchConvertMediaJob;
use OCA\WorkflowMediaConverter\BackgroundJobs\ConvertMediaJob;

class BatchConvertMediaJobTest extends BackgroundJobTest
{
    protected $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configService->expects()->setBatchStatus(\Mockery::any(), 'seeking');
        $this->configService->expects()->setBatchStatus(\Mockery::any(), 'converting');

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
        $arguments = $this->createTestArguments([
            'postConversionSourceRuleMoveFolder' => 'test',
            'postConversionOutputRuleMoveFolder' => 'test',
            'postConversionOutputConflictRuleMoveFolder' => 'test',
        ]);

        $this->configService->allows()->setUserId($arguments['user_id']);

        $result = $this->job->parseArguments($arguments);

        $this->rootFolder->shouldHaveReceived()->get($arguments['sourceFolder']);

        $this->assertEquals($arguments['user_id'], $this->job->userId);

        $this->assertEquals($this->job, $result);
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
        $arguments = $this->setJobArguments();

        $folder = $this->createTestFolder();

        $unconvertedMedia = [
            ['path' => '/files/admin/source-folder/test-1.mov', 'node' => $this->createFile($folder, 'test-1.mov', '/files/admin/source-folder')],
            ['path' => '/files/admin/source-folder/test-2.mov', 'node' => $this->createFile($folder, 'test-2.mov', '/files/admin/source-folder')],
        ];

        $this->job->unconvertedMedia = array_map(function ($x) {
            return $x['node'];
        }, $unconvertedMedia);

        $this->configService->allows()->updateBatch($this->job->batchId, ['unconverted' => 2]);

        $result = $this->job->queueUnconvertedMediaForConversion();

        foreach ($unconvertedMedia as $media) {
            $this->jobList->shouldHaveReceived('add')->with(ConvertMediaJob::class, [
                'user_id'                                    => $arguments['user_id'],
                'batch_id'                                   => $arguments['id'],
                'path'                                       => $media['path'],
                'outputExtension'                            => $arguments['outputExtension'],
                'postConversionSourceRule'                   => $arguments['postConversionSourceRule'],
                'postConversionSourceRuleMoveFolder'         => $arguments['postConversionSourceRuleMoveFolder'],
                'postConversionOutputRule'                   => $arguments['postConversionOutputRule'],
                'postConversionOutputRuleMoveFolder'         => $arguments['postConversionOutputRuleMoveFolder'],
                'postConversionOutputConflictRule'           => $arguments['postConversionOutputConflictRule'],
                'postConversionOutputConflictRuleMoveFolder' => $arguments['postConversionOutputConflictRuleMoveFolder'],
            ]);
        }

        $this->assertEquals($this->job, $result);
    }

    public function test_Run_ShouldBatchConvertMedia()
    {
        $arguments = $this->createTestArguments();

        $this->configService->allows()->setUserId($arguments['user_id']);

        $this->rootFolder->expects()->get($arguments['sourceFolder'])->andReturns($this->videoFolder);

        $this->videoFolder->allows()->nodeExists('test-1.mp4')->andReturns(false);
        $this->videoFolder->allows()->nodeExists('test-2.mp4')->andReturns(false);
        $this->videoFolder->allows()->nodeExists('test-3.mp4')->andReturns(true);
        $this->videoSubfolder->allows()->nodeExists('test-1.mp4')->andReturns(false);
        $this->videoSubfolder->allows()->nodeExists('test-2.mp4')->andReturns(false);
        $this->videoSubfolder->allows()->nodeExists('test-3.mp4')->andReturns(true);

        $this->configService->expects()->updateBatch($arguments['id'], [
            'unconverted' => 4
        ]);

        $this->job->run($arguments);

        $this->assertTrue(true);
    }

    protected function createTestArguments($overrides = [])
    {
        return array_merge([
            'user_id' => 'admin',
            'id' => 'rjmoalgbvoekv4yy11ijegpjpnk90gmv',
            'status' => 'queued',
            'sourceFolderPath' => '/files/admin/source-folder',
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
}

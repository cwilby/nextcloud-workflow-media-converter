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
        $arguments = $this->createJobArguments([
            'postConversionSourceRuleMoveFolder' => 'test',
            'postConversionOutputRuleMoveFolder' => 'test',
            'postConversionOutputConflictRuleMoveFolder' => 'test',
        ]);

        $this->configService->expects()->setUserId($arguments['user_id']);
        $this->rootFolder->expects()->get($arguments['sourceFolder']);

        $result = $this->job->parseArguments($arguments);

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

        $folder = $this->createTestFolder('/admin/files spot /source-folder');

        $unconvertedMedia = [
            ['path' => '/admin/files spot /source-folder/test-1.mov', 'node' => $this->createFile($folder, 'test-1.mov', '/admin/files spot /source-folder')],
            ['path' => '/admin/files spot /source-folder/test-2.mov', 'node' => $this->createFile($folder, 'test-2.mov', '/admin/files spot /source-folder')],
        ];

        $this->job->unconvertedMedia = array_map(function ($x) {
            return $x['node'];
        }, $unconvertedMedia);

        $this->configService->expects()->updateBatch($this->job->batchId, ['unconverted' => 2]);

        foreach ($unconvertedMedia as $media) {
            $this->jobList->expects('add')->with(ConvertMediaJob::class, [
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

        $result = $this->job->queueUnconvertedMediaForConversion();

        $this->assertEquals($this->job, $result);
    }

    public function test_Run_ShouldBatchConvertMedia()
    {
        $arguments = $this->createJobArguments();

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

        $this->jobList->expects()->add(ConvertMediaJob::class, \Mockery::any())->twice();

        $this->job->run($arguments);

        $this->assertTrue(true);
    }

    protected function createJobArguments($overrides = [])
    {
        return array_merge([
            'user_id' => 'admin',
            'id' => 'rjmoalgbvoekv4yy11ijegpjpnk90gmv',
            'status' => 'queued',
            'sourceFolder' => '/admin/files/source-folder',
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

        $this->rootFolder->expects()->get($arguments['sourceFolder'])->once();

        return $arguments;
    }
}

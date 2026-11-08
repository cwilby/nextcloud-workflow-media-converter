<?php

namespace OCA\WorkflowMediaConverter\Tests\BackgroundJobs;

use OCA\WorkflowMediaConverter\BackgroundJobs\ConvertMediaJob;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

class RecordingConversionJob extends ConvertMediaJob {
	public array $events = [];
	public bool $failWrite = false;

	public function __construct() {
	}

	public function writePostConversionOutputFile() {
		$this->events[] = 'write';
		if ($this->failWrite) {
			throw new RuntimeException('Storage unavailable');
		}
		return $this;
	}

	public function handlePostConversionSourceFile() {
		$this->events[] = 'source';
	}
}

class SafeSourceDeletionTest extends TestCase {
	private function job(string $rule): RecordingConversionJob {
		$job = new RecordingConversionJob();
		$property = new ReflectionProperty(ConvertMediaJob::class, 'postConversionSourceRule');
		$property->setValue($job, $rule);
		return $job;
	}

	public function testDeletesSourceOnlyAfterOutputIsSaved(): void {
		$job = $this->job('delete');
		$job->handlePostConversion();
		self::assertSame(['write', 'source'], $job->events);
	}

	public function testFailedWriteDoesNotDeleteSource(): void {
		$job = $this->job('delete');
		$job->failWrite = true;
		try {
			$job->handlePostConversion();
			self::fail('Expected storage failure');
		} catch (RuntimeException $e) {
			self::assertSame('Storage unavailable', $e->getMessage());
		}
		self::assertSame(['write'], $job->events);
	}

	public function testSameFormatOverwriteKeepsSourceUntilDistinctOutputIsStored(): void {
		[$job, $state] = $this->sameFormatJob(true);
		$job->handlePostConversion();
		self::assertSame(['write:video (1).mp4', 'delete-source'], $state->events);
	}

	public function testFalseStorageResultKeepsSameFormatSource(): void {
		[$job, $state] = $this->sameFormatJob(false);
		try {
			$job->handlePostConversion();
			self::fail('Expected storage failure');
		} catch (RuntimeException $e) {
			self::assertSame('Unable to store converted output; source retained.', $e->getMessage());
		}
		self::assertSame(['write:video (1).mp4'], $state->events);
	}

	private function sameFormatJob(bool $writeSucceeds): array {
		$state = (object)['events' => []];
		$source = new class($state) {
			public function __construct(
				private object $state,
			) {
			}
			public function getId() {
				return 7;
			}
			public function delete() {
				$this->state->events[] = 'delete-source';
			}
		};
		$folder = new class($source) {
			public function __construct(
				private object $source,
			) {
			}
			public function nodeExists($name) {
				return $name === 'video.mp4';
			}
			public function get($name) {
				return $this->source;
			}
			public function getPath() {
				return '/user/files';
			}
		};
		$view = new class($state, $writeSucceeds) {
			public function __construct(
				private object $state,
				private bool $writeSucceeds,
			) {
			}
			public function fromTmpFile($path, $name) {
				$this->state->events[] = 'write:' . $name;
				return $this->writeSucceeds;
			}
		};
		$factory = new class($view) {
			public function __construct(
				private object $view,
			) {
			}
			public function create($path) {
				return $this->view;
			}
		};
		$job = (new \ReflectionClass(ConvertMediaJob::class))->newInstanceWithoutConstructor();
		foreach ([
			'postConversionSourceRule' => 'delete',
			'postConversionOutputConflictRule' => 'overwrite',
			'postConversionTimestampRule' => 'conversionTime',
			'outputFileName' => 'video.mp4',
			'outputExtension' => 'mp4',
			'tempOutputPath' => '/tmp/converted.mp4',
			'outputFolder' => $folder,
			'sourceFile' => $source,
			'viewFactory' => $factory,
		] as $key => $value) {
			(new ReflectionProperty(ConvertMediaJob::class, $key))->setValue($job, $value);
		}
		return [$job, $state];
	}

	public function testMoveBehaviorIsUnchanged(): void {
		$job = $this->job('move');
		$job->handlePostConversion();
		self::assertSame(['source', 'write'], $job->events);
	}
}

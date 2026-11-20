<?php

namespace OCA\WorkflowMediaConverter\Tests\Service;

use OCA\WorkflowMediaConverter\Service\ConfigService;
use PHPUnit\Framework\TestCase;

class InMemoryConfigService extends ConfigService {
	public array $batches = [];

	public function __construct() {
	}

	public function getConfigValueJson($key, $default = '[]') {
		return $this->batches;
	}

	public function setConfigValueJson($key, $value = []) {
		$this->batches = $value;
	}
}

class ConfigServiceTest extends TestCase {
	public function testUpdatingOneBatchPreservesOtherBatches(): void {
		$service = new InMemoryConfigService();
		$service->batches = [['id' => 'first', 'converted' => 1], ['id' => 'second', 'converted' => 3]];
		$service->updateBatch('first', ['converted' => 2]);
		self::assertSame([['id' => 'first', 'converted' => 2], ['id' => 'second', 'converted' => 3]], $service->batches);
	}

	public function testMissingBatchDoesNotResolveToFirstBatch(): void {
		$service = new InMemoryConfigService();
		$original = [['id' => 'first', 'status' => 'converting']];
		$service->batches = $original;
		self::assertNull($service->getBatch('missing'));
		$service->updateBatch('missing', ['converted' => 99]);
		$service->setBatchStatus('missing', 'failed');
		self::assertSame($original, $service->batches);
	}
}

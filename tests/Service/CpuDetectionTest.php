<?php

namespace OCA\WorkflowMediaConverter\Tests\Service;

use OCA\WorkflowMediaConverter\Service\ConfigService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CpuDetectionTest extends TestCase {
	public function testRestrictedLinuxUsesFallbackWithoutCallingSysctl(): void {
		if (PHP_OS_FAMILY !== 'Linux' || is_file('/proc/cpuinfo')) {
			self::markTestSkipped('Requires a Linux environment without /proc/cpuinfo');
		}

		$code = <<<'CODE'
require $argv[1];
$service = (new ReflectionClass('OCA\\WorkflowMediaConverter\\Service\\ConfigService'))->newInstanceWithoutConstructor();
echo (new ReflectionMethod($service, 'getMaxThreads'))->invoke($service);
CODE;
		$descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
		$process = proc_open([PHP_BINARY, '-r', $code, (new ReflectionClass(ConfigService::class))->getFileName()], $descriptors, $pipes);
		self::assertIsResource($process);
		fclose($pipes[0]);
		$output = stream_get_contents($pipes[1]);
		$error = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		self::assertSame(0, proc_close($process));
		self::assertSame('4', $output);
		self::assertSame('', $error);
	}
}

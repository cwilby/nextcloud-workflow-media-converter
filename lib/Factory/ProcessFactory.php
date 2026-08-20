<?php

namespace OCA\WorkflowMediaConverter\Factory;

use Symfony\Component\Process\Process;

class ProcessFactory {
	public function create(array $command) {
		return new Process($command, null, null, null, null);
	}
}

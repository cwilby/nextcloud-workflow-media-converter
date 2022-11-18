<?php

namespace OCA\WorkflowMediaConverter\Factory;

use Symfony\Component\Process\Process;

class ProcessFactory {
	public function create($command) {
		return Process::fromShellCommandline($command, null, null, null, null);
	}
}

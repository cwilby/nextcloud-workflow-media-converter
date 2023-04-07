<?php

namespace OCA\WorkflowMediaConverter\Factory;

class LockFactory {
	public function create($state) {
		return $state ? date('Y-m-d H:i:s') : null;
	}
}

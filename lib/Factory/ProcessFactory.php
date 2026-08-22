<?php

namespace OCA\WorkflowMediaConverter\Factory;

use Symfony\Component\Process\Process;

class ProcessFactory {
	public function create(array $command) {
		return new Process($command, null, null, null, null);
	}

	public function parseFlags(string $flags): array
	{
		$arguments = [];
		$length = strlen($flags);
		$argument = '';
		$quote = null;
		$escaping = false;

		for ($i = 0; $i < $length; $i++) {
			$character = $flags[$i];

			if ($escaping) {
				$argument .= $character;
				$escaping = false;
				continue;
			}

			if ($character === '\\') {
				$escaping = true;
				continue;
			}

			if ($quote !== null) {
				if ($character === $quote) {
					$quote = null;
				} else {
					$argument .= $character;
				}

				continue;
			}

			if ($character === '"' || $character === "'") {
				$quote = $character;
				continue;
			}

			if (ctype_space($character)) {
				if ($argument !== '') {
					$arguments[] = $argument;
					$argument = '';
				}

				continue;
			}

			$argument .= $character;
		}

		if ($escaping) {
			$argument .= '\\';
		}

		if ($quote !== null) {
			throw new \InvalidArgumentException('Unterminated quote in FFmpeg flags.');
		}

		if ($argument !== '') {
			$arguments[] = $argument;
		}

		return $arguments;
	}
}

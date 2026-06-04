<?php
namespace Devture\Bundle\NagiosBundle\Status;

use Devture\Bundle\NagiosBundle\Exception\ParseException;
use Devture\Bundle\NagiosBundle\Exception\FileMissingException;

class Fetcher {

	const STATE_FREE = 0;
	const STATE_DEFINITION = 1;

	private $statusFilePath;

	public function __construct($statusFilePath) {
		$this->statusFilePath = $statusFilePath;
	}

	/**
	 * @throws FileMissingException
	 * @return \Devture\Bundle\NagiosBundle\Status\Status[]
	 */
	public function fetch() {
		if (!file_exists($this->statusFilePath)) {
			throw new FileMissingException(sprintf('Cannot find status file at `%s`', $this->statusFilePath));
		}
		return $this->parse(file_get_contents($this->statusFilePath));
	}

	private function parse($contents) {
		$lines = explode("\n", $contents);
		$state = self::STATE_FREE;
		$lastDefinitionType = null;

		$objects = array();

		foreach ($lines as $line) {
			if ($line === '') {
				continue;
			}

			if ($state === self::STATE_FREE) {
				if (preg_match('/^(.+?)\s{$/', $line, $matches)) {
					$lastDefinitionType = $matches[1];
					$lastDefinitionDirectives = array();
					$state = self::STATE_DEFINITION;
					continue;
				}

				if (strpos($line, '#') === 0) {
					continue;
				}

				throw new ParseException('Unexpected line during free state: ' . $line);
			}

			if ($state === self::STATE_DEFINITION) {
				$line = ltrim($line);

				if ($line === '}') {
					if ($lastDefinitionType === Status::TYPE_SERVICE_STATUS) {
						$objects[] = new ServiceStatus($lastDefinitionType, $lastDefinitionDirectives);
					} else if ($lastDefinitionType === Status::TYPE_PROGRAM_STATUS) {
						$objects[] = new ProgramStatus($lastDefinitionType, $lastDefinitionDirectives);
					} else if ($lastDefinitionType === Status::TYPE_INFO) {
						$objects[] = new InfoStatus($lastDefinitionType, $lastDefinitionDirectives);
					}
					$state = self::STATE_FREE;
					continue;
				}

				$parts = explode('=', $line, 2);
				if (!isset($parts[1])) {
					throw new ParseException('Unexpected line during definition state: ' . $line);
				}

				$lastDefinitionDirectives[$parts[0]] = $parts[1];
			}
		}

		return $objects;
	}

}

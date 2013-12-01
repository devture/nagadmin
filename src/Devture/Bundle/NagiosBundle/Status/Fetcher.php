<?php
namespace Devture\Bundle\NagiosBundle\Status;

use Devture\Bundle\NagiosBundle\Exception\ParseException;
use Devture\Bundle\NagiosBundle\Exception\StatusFileMissingException;

class Fetcher {

	const STATE_FREE = 0;
	const STATE_DEFINITION = 1;

	private $statusFilePath;

	public function __construct($statusFilePath) {
		$this->statusFilePath = $statusFilePath;
	}

	/**
	 * @throws StatusFileMissingException
	 * @return \Devture\Bundle\NagiosBundle\Status\Status[]
	 */
	public function fetch() {
		if (!file_exists($this->statusFilePath)) {
			throw new StatusFileMissingException(sprintf('Cannot find status file at `%s`', $this->statusFilePath));
		}
		return $this->parse(file_get_contents($this->statusFilePath));
	}

	private function parse($contents) {
		$lines = explode("\n", $contents);
		$state = self::STATE_FREE;
		$lastDefinitionType = null;

		$objects = array();

		foreach ($lines as $line) {
			if (strpos($line, '#') === 0) {
				continue;
			}

			if ($line === '') {
				continue;
			}

			$line = trim($line);

			if ($state === self::STATE_FREE) {
				if (preg_match('/^(.+?)\s{$/', $line, $matches)) {
					$lastDefinitionType = $matches[1];
					$lastDefinitionDirectives = array();
					$state = self::STATE_DEFINITION;
					continue;
				}

				throw new ParseException('Unexpected line during free state: ' . $line);
			}

			if ($state === self::STATE_DEFINITION) {
				if (preg_match('/^(.+?)=(.*)$/', $line, $matches)) {
					list($_fullMatch, $directiveName, $directiveValue) = $matches;
					$lastDefinitionDirectives[$directiveName] = $directiveValue;
					continue;
				}

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

				throw new ParseException('Unexpected line during definition state: ' . $line);
			}
		}

		return $objects;
	}

}
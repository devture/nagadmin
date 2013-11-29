<?php
namespace Devture\Bundle\NagiosBundle\Status;

use Devture\Bundle\NagiosBundle\Exception\ParseException;

class Fetcher {

	const STATE_FREE = 0;
	const STATE_DEFINITION = 1;

	private $statusFilePath;

	public function __construct($statusFilePath) {
		$this->statusFilePath = $statusFilePath;
	}

	public function fetch() {
		if (!file_exists($this->statusFilePath)) {
			throw new \InvalidArgumentException('Cannot find status file: ' . $this->statusFilePath);
		}
		return $this->parse(file_get_contents($this->statusFilePath));
	}

	public function parse($contents) {
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
					//Ignore other definition types - we only care about this for now.
					if ($lastDefinitionType === Status::TYPE_SERVICE_STATUS) {
						$objects[] = new ServiceStatus($lastDefinitionType, $lastDefinitionDirectives);
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
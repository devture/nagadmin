<?php
namespace Devture\Bundle\NagiosBundle\Log;

use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Model\Service;

class LogEntry {

	private string $type;
	private int $timestamp;
	private string $value;
	private ?Host $host;
	private ?Service $service;

	public function __construct(string $type, int $timestamp, string $value, ?Host $host, ?Service $service) {
		$this->type = $type;
		$this->timestamp = $timestamp;
		$this->value = $value;
		$this->host = $host;
		$this->service = $service;
	}

	public function getId(): string {
		return sha1($this->type . $this->timestamp . $this->value);
	}

	public function getType(): string {
		return $this->type;
	}

	public function getTimestamp(): int {
		return $this->timestamp;
	}

	public function getValue(): string {
		return $this->value;
	}

	/**
	 * @return Host|NULL
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * @return Service|NULL
	 */
	public function getService() {
		return $this->service;
	}

}

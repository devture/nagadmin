<?php
namespace Devture\Bundle\NagiosBundle\Log;

use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Model\Service;

class LogEntry {

	private $type;
	private $timestamp;
	private $value;
	private $host;
	private $service;

	public function __construct($type, $timestamp, $value, Host $host = null, Service $service = null) {
		$this->type = $type;
		$this->timestamp = $timestamp;
		$this->value = $value;
		$this->host = $host;
		$this->service = $service;
	}

	public function getId() {
		return sha1($this->type . $this->timestamp . $this->value);
	}

	public function getType() {
		return $this->type;
	}

	public function getTimestamp() {
		return $this->timestamp;
	}

	public function getValue() {
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
<?php
namespace Devture\Bundle\NagiosBundle\Model;

class HostInfo {

	private $service;
	private $servicesInfo;

	public function __construct(Host $host, array $servicesInfo) {
		$this->host = $host;
		$this->servicesInfo = $servicesInfo;
	}

	/**
	 * @return Host
	 */
	public function getHost() {
		return $this->host;
	}

	public function getServicesInfo() {
		return $this->servicesInfo;
	}

}
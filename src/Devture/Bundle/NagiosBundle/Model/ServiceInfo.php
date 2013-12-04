<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Bundle\NagiosBundle\Status\ServiceStatus;

class ServiceInfo {

	private $service;
	private $status;

	public function __construct(Service $service, ServiceStatus $status = null) {
		$this->service = $service;
		$this->status = $status;
	}

	/**
	 * @return Service
	 */
	public function getService() {
		return $this->service;
	}

	/**
	 * @return ServiceStatus|NULL
	 */
	public function getStatus() {
		return $this->status;
	}

}
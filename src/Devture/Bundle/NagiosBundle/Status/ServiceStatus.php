<?php
namespace Devture\Bundle\NagiosBundle\Status;

class ServiceStatus extends Status {

	const STATUS_OK = 0;
	const STATUS_WARNING = 1;
	const STATUS_CRITICAL = 2;
	const STATUS_UNKNOWN = 3;

	public function getHostname() {
		return $this->getDirective('host_name');
	}

	public function getServiceDescription() {
		return $this->getDirective('service_description');
	}

	public function getCurrentState() {
		return (int) $this->getDirective('current_state');
	}

	public function getCurrentStateHuman() {
		$map = array(
			self::STATUS_OK => 'ok',
			self::STATUS_WARNING => 'warning',
			self::STATUS_CRITICAL => 'critical',
			self::STATUS_UNKNOWN => 'unknown',
		);

		$state = $this->getCurrentState();

		if (!isset($map[$state])) {
			throw new \InvalidArgumentException('Unknown state: ' . $state);
		}
		return $map[$state];
	}

}
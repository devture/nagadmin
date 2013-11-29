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
		return $this->humanizeState($this->getCurrentState());
	}

	public function getLastHardState() {
		return (int) $this->getDirective('last_hard_state');
	}

	public function getLastHardStateHuman() {
		return $this->humanizeState($this->getLastHardState());
	}

	public function getCurrentAttempt() {
		return (int) $this->getDirective('current_attempt');
	}

	public function getMaxAttempts() {
		return (int) $this->getDirective('max_attempts');
	}

	public function getPluginOutput() {
		return $this->getDirective('plugin_output');
	}

	private function humanizeState($state) {
		$map = array(
				self::STATUS_OK => 'ok',
				self::STATUS_WARNING => 'warning',
				self::STATUS_CRITICAL => 'critical',
				self::STATUS_UNKNOWN => 'unknown',
		);
		if (!isset($map[$state])) {
			throw new \InvalidArgumentException('Unknown state: ' . $state);
		}
		return $map[$state];
	}

}
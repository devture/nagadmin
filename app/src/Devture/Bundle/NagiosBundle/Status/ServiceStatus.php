<?php
namespace Devture\Bundle\NagiosBundle\Status;

class ServiceStatus extends Status {

	const STATUS_OK = 0;
	const STATUS_WARNING = 1;
	const STATUS_CRITICAL = 2;
	const STATUS_UNKNOWN = 3;

	/**
	 * @return string
	 */
	public function getHostname() {
		return $this->getDirective('host_name');
	}

	/**
	 * @return string
	 */
	public function getServiceDescription() {
		return $this->getDirective('service_description');
	}

	public function getCurrentState(): int {
		return (int) $this->getDirective('current_state');
	}

	public function getCurrentStateHuman(): string {
		return $this->humanizeState($this->getCurrentState());
	}

	public function getLastHardState(): int {
		return (int) $this->getDirective('last_hard_state');
	}

	public function getLastHardStateHuman(): string {
		return $this->humanizeState($this->getLastHardState());
	}

	public function getCurrentAttempt(): int {
		return (int) $this->getDirective('current_attempt');
	}

	public function getMaxAttempts(): int {
		return (int) $this->getDirective('max_attempts');
	}

	/**
	 * @return string
	 */
	public function getPluginOutput() {
		return $this->getDirective('plugin_output');
	}

	/**
	 * @return string
	 */
	public function getPerformanceData() {
		return $this->getDirective('performance_data');
	}

	public function getLastCheckTime(): int {
		return (int) $this->getDirective('last_check');
	}

	public function getNextCheckTime(): int {
		return (int) $this->getDirective('next_check');
	}

	public function getLastStateChangeTime(): int {
		return (int) $this->getDirective('last_state_change');
	}

	public function getLastHardStateChangeTime(): int {
		return (int) $this->getDirective('last_hard_state_change');
	}

	public function isChecked(): bool {
		return ((int) $this->getDirective('has_been_checked') === 1);
	}

	private function humanizeState(int $state): string {
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

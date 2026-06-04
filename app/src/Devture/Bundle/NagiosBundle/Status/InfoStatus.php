<?php
namespace Devture\Bundle\NagiosBundle\Status;

class InfoStatus extends Status {

	public function getCurrentVersion() {
		return $this->getDirective('version');
	}

	public function isUpdateAvailable() {
		return ((int) $this->getDirective('update_available') === 1);
	}

	public function getNewVersion() {
		return $this->getDirective('new_version');
	}

	/**
	 * Returns the time that the status file was last created/dumped to.
	 *
	 * @return int
	 */
	public function getCreationTime() {
		return (int) $this->getDirective('created');
	}

	/**
	 * Tells whether the status file information appears outdated.
	 *
	 * The status file is normally dumped to every X seconds.
	 * If it hasn't been done in a few minutes or more, something is likely wrong
	 * (or the status_update_interval variable is set too high - unlikely).
	 *
	 * @return boolean
	 */
	public function appearsOutdated() {
		$tolerance = 2 * 60;
		return ($this->getCreationTime() < time() - $tolerance);
	}

}
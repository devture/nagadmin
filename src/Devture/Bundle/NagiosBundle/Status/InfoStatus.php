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

}
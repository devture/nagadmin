<?php
namespace Devture\Bundle\NagiosBundle\Status;

class ProgramStatus extends Status {

	public function getPid() {
		return $this->getDirective('nagios_pid');
	}

	public function getStartTime() {
		return $this->getDirective('program_start');
	}

}
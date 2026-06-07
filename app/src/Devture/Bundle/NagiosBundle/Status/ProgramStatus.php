<?php
namespace Devture\Bundle\NagiosBundle\Status;

class ProgramStatus extends Status {

	/**
	 * @return string
	 */
	public function getPid() {
		return $this->getDirective('nagios_pid');
	}

	/**
	 * @return string
	 */
	public function getStartTime() {
		return $this->getDirective('program_start');
	}

}

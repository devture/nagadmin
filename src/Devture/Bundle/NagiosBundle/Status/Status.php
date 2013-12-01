<?php
namespace Devture\Bundle\NagiosBundle\Status;

abstract class Status {

	const TYPE_INFO = 'info';
	const TYPE_PROGRAM_STATUS = 'programstatus';
	const TYPE_SERVICE_STATUS = 'servicestatus';

	private $type;
	private $directives = array();

	public function __construct($type, $directives) {
		$this->type = $type;
		$this->directives = $directives;
	}

	public function getType() {
		return $this->type;
	}

	public function getDirective($name, $defaultValue = null) {
		return (isset($this->directives[$name]) ? $this->directives[$name] : $defaultValue);
	}

}
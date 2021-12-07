<?php
namespace Devture\Bundle\NagiosBundle\NagiosCommand;

class Submitter {

	private $commandFilePath;

	public function __construct($commandFilePath) {
		$this->commandFilePath = $commandFilePath;
	}

	public function submit($command) {
		file_put_contents($this->commandFilePath, $command . "\n", FILE_APPEND | LOCK_EX);
	}

}

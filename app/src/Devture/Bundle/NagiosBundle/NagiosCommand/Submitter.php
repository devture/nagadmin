<?php
namespace Devture\Bundle\NagiosBundle\NagiosCommand;

class Submitter {

	private string $commandFilePath;

	public function __construct(string $commandFilePath) {
		$this->commandFilePath = $commandFilePath;
	}

	/**
	 * @param string $command
	 * @return void
	 */
	public function submit($command) {
		file_put_contents($this->commandFilePath, $command . "\n", FILE_APPEND | LOCK_EX);
	}

}

<?php
namespace Devture\Bundle\NagiosBundle\NagiosCommand;

class Submitter {

	private string $commandFilePath;

	public function __construct(string $commandFilePath) {
		$this->commandFilePath = $commandFilePath;
	}

	/**
	 * Best-effort: the command file (a FIFO) only exists while Nagios is running.
	 *
	 * @param string $command
	 * @return void
	 */
	public function submit($command) {
		if (!file_exists($this->commandFilePath) || filetype($this->commandFilePath) !== 'fifo') {
			return;
		}
		file_put_contents($this->commandFilePath, $command . "\n", FILE_APPEND | LOCK_EX);
	}

}

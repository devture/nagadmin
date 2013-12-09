<?php
namespace Devture\Bundle\NagiosBundle\ConsoleCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command {

	private $container;

	public function __construct(\Pimple $container) {
		parent::__construct('install');

		$this->container = $container;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->getInstaller()->install();
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Install\Installer
	 */
	private function getInstaller() {
		return $this->container['devture_nagios.install.installer'];
	}

}

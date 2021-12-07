<?php
namespace Devture\Bundle\NagiosBundle\ConsoleCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command {

	private $container;

	public function __construct(\Pimple\Container $container) {
		parent::__construct('install');

		$this->container = $container;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->getInstaller()->install();
		return 0;
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Install\Installer
	 */
	private function getInstaller() {
		return $this->container['devture_nagios.install.installer'];
	}

}

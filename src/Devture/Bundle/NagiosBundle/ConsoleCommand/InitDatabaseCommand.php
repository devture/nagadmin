<?php
namespace Devture\Bundle\NagiosBundle\ConsoleCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class InitDatabaseCommand extends Command {

	private $container;

	public function __construct(\Pimple $container) {
		parent::__construct('init-database');

		$this->container = $container;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->getHostRepository()->ensureIndexes();
		$this->getServiceRepository()->ensureIndexes();
		$this->getCommandRepository()->ensureIndexes();
		$this->getContactRepository()->ensureIndexes();
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Repository\HostRepository
	 */
	protected function getHostRepository() {
		return $this->container['devture_nagios.host.repository'];
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Repository\ServiceRepository
	 */
	protected function getServiceRepository() {
		return $this->container['devture_nagios.service.repository'];
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Repository\CommandRepository
	 */
	protected function getCommandRepository() {
		return $this->container['devture_nagios.command.repository'];
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Repository\ContactRepository
	 */
	protected function getContactRepository() {
		return $this->container['devture_nagios.contact.repository'];
	}

}

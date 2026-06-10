<?php

namespace Devture\Bundle\NagiosBundle\ConsoleCommand;

use Devture\Bundle\NagiosBundle\Install\Installer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
	name: 'install',
	description: 'Performs initial installation: sets resource variables and deploys the Nagios configuration.',
)]
class InstallCommand extends Command
{
	public function __construct(private readonly Installer $installer)
	{
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->installer->install();

		(new SymfonyStyle($input, $output))->success('Installed: resource variables set and configuration deployed.');

		return Command::SUCCESS;
	}
}

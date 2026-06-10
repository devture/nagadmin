<?php

namespace Devture\Bundle\NagiosBundle\ConsoleCommand;

use Devture\Bundle\NagiosBundle\Repository\CommandRepository;
use Devture\Bundle\NagiosBundle\Repository\ContactRepository;
use Devture\Bundle\NagiosBundle\Repository\HostRepository;
use Devture\Bundle\NagiosBundle\Repository\ServiceRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
	name: 'init-database',
	description: 'Ensures the MongoDB indexes for the Nagios collections exist.',
)]
class InitDatabaseCommand extends Command
{
	public function __construct(
		private readonly HostRepository $hostRepository,
		private readonly ServiceRepository $serviceRepository,
		private readonly CommandRepository $commandRepository,
		private readonly ContactRepository $contactRepository,
	) {
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->hostRepository->ensureIndexes();
		$this->serviceRepository->ensureIndexes();
		$this->commandRepository->ensureIndexes();
		$this->contactRepository->ensureIndexes();

		(new SymfonyStyle($input, $output))->success('Database indexes ensured.');

		return Command::SUCCESS;
	}
}

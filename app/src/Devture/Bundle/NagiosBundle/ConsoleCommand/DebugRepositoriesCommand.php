<?php

namespace Devture\Bundle\NagiosBundle\ConsoleCommand;

use Devture\Bundle\NagiosBundle\Repository\CommandRepository;
use Devture\Bundle\NagiosBundle\Repository\ContactRepository;
use Devture\Bundle\NagiosBundle\Repository\HostRepository;
use Devture\Bundle\NagiosBundle\Repository\ResourceRepository;
use Devture\Bundle\NagiosBundle\Repository\ServiceRepository;
use Devture\Bundle\NagiosBundle\Repository\TimePeriodRepository;
use Devture\Bundle\UserBundle\Repository\UserRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
	name: 'nagadmin:debug:repositories',
	description: 'Counts entities through the ported repositories, exercising the full hydration graph.',
)]
class DebugRepositoriesCommand extends Command
{
	public function __construct(
		private readonly TimePeriodRepository $timePeriodRepository,
		private readonly CommandRepository $commandRepository,
		private readonly HostRepository $hostRepository,
		private readonly ContactRepository $contactRepository,
		private readonly ServiceRepository $serviceRepository,
		private readonly ResourceRepository $resourceRepository,
		private readonly UserRepositoryInterface $userRepository,
	) {
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);

		$rows = [
			['time_period', count($this->timePeriodRepository->findAll())],
			['command', count($this->commandRepository->findAll())],
			['host', count($this->hostRepository->findAll())],
			['contact', count($this->contactRepository->findAll())],
			['service', count($this->serviceRepository->findAll())],
			['resource', count($this->resourceRepository->findAll())],
			['devture_user', count($this->userRepository->findAll())],
		];

		$io->table(['Repository', 'findAll() count'], $rows);

		return Command::SUCCESS;
	}
}

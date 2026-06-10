<?php

namespace Devture\Bundle\NagiosBundle\ConsoleCommand;

use Devture\Bundle\NagiosBundle\Status\Manager as StatusManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
	name: 'check:status',
	description: "Reports Nagios' health from its status file (suitable as a monitoring check).",
)]
class CheckStatusCommand extends Command
{
	const STATUS_OK = 0;
	const STATUS_WARNING = 1;
	const STATUS_CRITICAL = 2;
	const STATUS_UNKOWN = 3;

	public function __construct(
		private readonly StatusManager $statusManager,
		#[Autowire('%nagadmin.nagios.status_file_path%')]
		private readonly string $statusFilePath,
	) {
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$info = $this->statusManager->getInfoStatus();
		$program = $this->statusManager->getProgramStatus();

		if ($info === null || $program === null) {
			$output->write(sprintf('Nagios DOWN or status file path (%s) not configured properly or unreadable.', $this->statusFilePath));
			return self::STATUS_UNKOWN;
		}

		$date = function ($timestamp) {
			$dateTime = new \DateTime();
			$dateTime->setTimestamp($timestamp);
			$dateTime->setTimezone(new \DateTimeZone('UTC'));
			return $dateTime->format('Y-m-d, H:i:s') . ' (UTC)';
		};

		if ($info->appearsOutdated()) {
			$output->write(sprintf(
				"Nagios potentially down - status file last updated on %s",
				$date($info->getCreationTime()),
			));
			return self::STATUS_WARNING;
		}

		$output->write(sprintf(
			"Nagios %s (PID: %d), up since %s. Status file updated: %s",
			$info->getCurrentVersion(),
			$program->getPid(),
			$date($program->getStartTime()),
			$date($info->getCreationTime()),
		));

		return self::STATUS_OK;
	}
}

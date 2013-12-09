<?php
namespace Devture\Bundle\NagiosBundle\ConsoleCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class CheckStatusCommand extends Command {

	const STATUS_OK = 0;
	const STATUS_WARNING = 1;
	const STATUS_CRITICAL = 2;
	const STATUS_UNKOWN = 3;

	private $statusFilePath;
	private $container;

	public function __construct($statusFilePath, \Pimple $container) {
		parent::__construct('check:status');

		$this->statusFilePath = $statusFilePath;
		$this->container = $container;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$info = $this->getStatusManager()->getInfoStatus();
		$program = $this->getStatusManager()->getProgramStatus();

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
				$date($info->getCreationTime())
			));
			return self::STATUS_WARNING;
		}

		return $output->write(sprintf(
			"Nagios %s (PID: %d), up since %s. Status file updated: %s",
			$info->getCurrentVersion(),
			$program->getPid(),
			$date($program->getStartTime()),
			$date($info->getCreationTime())
		));
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Status\Manager
	 */
	private function getStatusManager() {
		return $this->container['devture_nagios.status.manager'];
	}

}

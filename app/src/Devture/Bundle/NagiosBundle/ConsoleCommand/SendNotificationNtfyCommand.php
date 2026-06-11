<?php

namespace Devture\Bundle\NagiosBundle\ConsoleCommand;

use Devture\Bundle\NagiosBundle\Notification\NtfySender;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'send-notification:ntfy',
	description: 'Sends an ntfy notification message.',
)]
class SendNotificationNtfyCommand extends Command
{
	public function __construct(
		private readonly NtfySender $ntfySender,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->addArgument('topicUrl', InputArgument::REQUIRED, 'The full topic URL to send to (e.g. https://ntfy.sh/my_topic).');
		$this->addArgument('title', InputArgument::REQUIRED, 'The notification title.');
		$this->addArgument('message', InputArgument::REQUIRED, 'The message to send.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->ntfySender->send(
			$input->getArgument('topicUrl'),
			$input->getArgument('title'),
			$input->getArgument('message'),
		);

		return Command::SUCCESS;
	}
}

<?php

namespace Devture\Bundle\NagiosBundle\ConsoleCommand;

use Devture\Bundle\NagiosBundle\Notification\SmsSender;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'send-notification:sms',
	description: 'Sends an SMS notification message.',
)]
class SendNotificationSmsCommand extends Command
{
	public function __construct(
		private readonly SmsSender $smsSender,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->addArgument('phoneNumber', InputArgument::REQUIRED, 'The phone number to send the SMS to.');
		$this->addArgument('message', InputArgument::REQUIRED, 'The message to send.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->smsSender->send(
			$input->getArgument('phoneNumber'),
			$input->getArgument('message'),
		);

		return Command::SUCCESS;
	}
}

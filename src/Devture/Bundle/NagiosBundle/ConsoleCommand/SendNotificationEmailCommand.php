<?php
namespace Devture\Bundle\NagiosBundle\ConsoleCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class SendNotificationEmailCommand extends Command {

	private $senderEmailAddress;
	private $container;

	public function __construct($senderEmailAddress, \Pimple\Container $container) {
		parent::__construct('send-notification:email');

		$this->senderEmailAddress = $senderEmailAddress;
		$this->container = $container;
	}

	protected function configure() {
		$this->addArgument('emailAddress', InputArgument::REQUIRED, 'The email address to send the message to.');
		$this->addArgument('subject', InputArgument::REQUIRED, 'The subject of the message.');
		$this->setDescription('Sends an Email notification message.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$message = new \Swift_Message($input->getArgument('subject'));
		$message->setFrom($this->senderEmailAddress);
		$message->setTo($input->getArgument('emailAddress'));
		$message->setBody(file_get_contents('php://stdin'));

		$this->getNotificationEmailMailer()->send($message);

		return 0;
	}

	/**
	 * @return \Swift_Mailer
	 */
	private function getNotificationEmailMailer() {
		return $this->container['devture_nagios.notification.email.mailer'];
	}

}

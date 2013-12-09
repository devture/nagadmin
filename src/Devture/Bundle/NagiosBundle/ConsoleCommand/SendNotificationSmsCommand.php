<?php
namespace Devture\Bundle\NagiosBundle\ConsoleCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Devture\Component\SmsSender\Message;

class SendNotificationSmsCommand extends Command {

	private $smsSenderId;
	private $container;

	public function __construct($smsSenderId, \Pimple $container) {
		parent::__construct('send-notification:sms');

		$this->smsSenderId = $smsSenderId;
		$this->container = $container;
	}

	protected function configure() {
		$this->addArgument('phoneNumber', InputArgument::REQUIRED, 'The phone number to send the SMS to.');
		$this->addArgument('message', InputArgument::REQUIRED, 'The message to send.');
		$this->setDescription('Sends an SMS notification message.');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$phoneNumber = $input->getArgument('phoneNumber');
		$messageText = $input->getArgument('message');

		$message = new Message($this->smsSenderId, $phoneNumber, $messageText);

		$this->getNotificationSmsGateway()->send($message);
	}

	/**
	 * @return \Devture\Component\SmsSender\Gateway\GatewayInterface
	 */
	private function getNotificationSmsGateway() {
		return $this->container['devture_nagios.notification.sms.gateway'];
	}

}

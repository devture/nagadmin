<?php

namespace Devture\Bundle\NagiosBundle\ConsoleCommand;

use Devture\Component\SmsSender\Gateway\GatewayInterface;
use Devture\Component\SmsSender\Message;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'send-notification:sms',
    description: 'Sends an SMS notification message.',
)]
class SendNotificationSmsCommand extends Command
{
    public function __construct(
        private readonly GatewayInterface $smsGateway,
        #[Autowire('%nagadmin.sms.sender_id%')] private readonly string $smsSenderId,
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
        $message = new Message(
            $this->smsSenderId,
            $input->getArgument('phoneNumber'),
            $input->getArgument('message'),
        );

        $this->smsGateway->send($message);

        return Command::SUCCESS;
    }
}

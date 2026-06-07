<?php

namespace Devture\Bundle\NagiosBundle\ConsoleCommand;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'send-notification:email',
    description: 'Sends an Email notification message.',
)]
class SendNotificationEmailCommand extends Command
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire('%nagadmin.notifications.sender_email%')] private readonly string $senderEmailAddress,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('emailAddress', InputArgument::REQUIRED, 'The email address to send the message to.');
        $this->addArgument('subject', InputArgument::REQUIRED, 'The subject of the message.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $message = (new Email())
            ->from($this->senderEmailAddress)
            ->to($input->getArgument('emailAddress'))
            ->subject($input->getArgument('subject'))
            ->text((string) file_get_contents('php://stdin'));

        $this->mailer->send($message);

        return Command::SUCCESS;
    }
}

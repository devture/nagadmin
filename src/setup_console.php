<?php
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Devture\Component\SmsSender\Message;

$app['devture_nagios.console.command.send_notification.email'] = function ($app) {
	$command = new Command('send-notification:email');
	$command->addArgument('emailAddress', InputArgument::REQUIRED, 'The email address to send the message to.');
	$command->addArgument('subject', InputArgument::REQUIRED, 'The subject of the message.');
	$command->setDescription('Sends an Email notification message.');
	$command->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
		$message = \Swift_Message::newInstance();
		$message->setSubject($input->getArgument('subject'));
		$message->setFrom($app['devture_nagios.notification.email.sender_email_address']);
		$message->setTo($input->getArgument('emailAddress'));
		$message->setBody(file_get_contents('php://stdin'));

		$app['devture_nagios.notification.email.mailer']->send($message);
	});
	return $command;
};

$app['devture_nagios.console.command.send_notification.sms'] = function ($app) {
	$command = new Command('send-notification:sms');
	$command->addArgument('phoneNumber', InputArgument::REQUIRED, 'The phone number to send the SMS to.');
	$command->addArgument('message', InputArgument::REQUIRED, 'The message to send.');
	$command->setDescription('Sends an SMS notification message.');
	$command->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
		$phoneNumber = $input->getArgument('phoneNumber');
		$messageText = $input->getArgument('message');

		$message = new Message($app['devture_nagios.notification.sms.sender_id'], $phoneNumber, $messageText);

		$app['devture_nagios.notification.sms.gateway']->send($message);
	});
	return $command;
};

$app['devture_nagios.console.command.install'] = function ($app) {
	$command = new Command('install');
	$command->setDescription('Installs the system.');
	$command->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
		$app['devture_nagios.install.installer']->install();
	});
	return $command;
};

$app['console'] = new Application();
$app['console']->add($app['devture_nagios.console.command.send_notification.email']);
$app['console']->add($app['devture_nagios.console.command.send_notification.sms']);
$app['console']->add($app['devture_nagios.console.command.install']);
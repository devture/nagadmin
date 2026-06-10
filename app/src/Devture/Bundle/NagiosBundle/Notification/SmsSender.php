<?php

namespace Devture\Bundle\NagiosBundle\Notification;

use Vonage\Client as VonageClient;
use Vonage\SMS\Message\SMS;

/**
 * Sends SMS notifications (notification API + console command) through the
 * Vonage SDK.
 *
 * When suppression is enabled (the default in development, where — unlike for
 * e-mail — there is no SMS catcher), messages are silently dropped instead of
 * being delivered, so the development environment never sends real text messages.
 */
class SmsSender
{
	public function __construct(
		private readonly VonageClient $vonageClient,
		private readonly string $senderNameOrNumber,
		private readonly bool $suppressSending,
	) {
	}

	public function send(string $phoneNumber, string $text): void
	{
		if ($this->suppressSending) {
			return;
		}

		$this->vonageClient->sms()->send(
			new SMS($phoneNumber, $this->senderNameOrNumber, $text),
		);
	}
}

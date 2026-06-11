<?php

namespace Devture\Bundle\NagiosBundle\Notification;

use GuzzleHttp\ClientInterface;

/**
 * Sends ntfy (https://ntfy.sh) notifications (notification API + console command)
 * by POST-ing to a full topic URL (e.g. https://ntfy.sh/my_topic), so each
 * contact can use any topic on any server.
 *
 * When suppression is enabled (NAGADMIN_NOTIFICATIONS_SUPPRESS_SENDING — the
 * default in development, where there is no ntfy catcher), messages are silently
 * dropped instead of being delivered.
 */
class NtfySender
{
	public function __construct(
		private readonly ClientInterface $httpClient,
		private readonly bool $suppressSending,
	) {
	}

	public function send(string $topicUrl, string $title, string $message): void
	{
		if ($this->suppressSending) {
			return;
		}

		$this->httpClient->request('POST', $topicUrl, [
			// HTTP headers are latin1-only; ntfy expects anything beyond that
			// to arrive RFC 2047-encoded.
			'headers' => ['Title' => sprintf('=?UTF-8?B?%s?=', base64_encode($title))],
			'body' => $message,
		]);
	}
}

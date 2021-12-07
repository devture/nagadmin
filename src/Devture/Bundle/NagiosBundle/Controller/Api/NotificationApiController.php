<?php
namespace Devture\Bundle\NagiosBundle\Controller\Api;

use Devture\Component\Form\Helper\StringHelper;
use Devture\Component\SmsSender\Message;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationApiController extends \Devture\Bundle\NagiosBundle\Controller\BaseController {

	public function __construct(
		\Silex\Application $app,
		string $namespace,
		private string $apiSecret,
		private string $smsSenderId,
		private string $senderEmailAddress,
	) {
		parent::__construct($app, $namespace);
	}

	public function sendSms(Request $request): Response {
		$responseOrNull = $this->authOrPrepareFailureResponse($request);
		if ($responseOrNull !== null) {
			return $responseOrNull;
		}

		$parts = explode("\n", trim(file_get_contents('php://input')), 2);
		if (count($parts) !== 2) {
			return $this->json([
				'ok' => false,
				'message' => sprintf(
					'Payload needs to contain 2 new-line separated parts: email address, subject, message text (may contain new lines). Got %d parts',
					count($parts),
				),
			], 400);
		}

		list($phoneNumber, $messageText) = $parts;

		if (!$phoneNumber) {
			return $this->json(['ok' => false, 'message' => 'Empty phone number parameter in body payload'], 422);
		}
		if (!$messageText) {
			return $this->json(['ok' => false, 'message' => 'Empty message text parameter in body payload'], 422);
		}

		$message = new Message($this->smsSenderId, $phoneNumber, $messageText);

		try {
			$this->getNotificationSmsGateway()->send($message);
		} catch (\Devture\Component\SmsSender\Exception\SendingThrottledException $e) {
			return $this->json([
				'ok' => false,
				'message' => sprintf('Sending throttled: %s', $e->getMessage()),
			], 503);
		} catch (\Devture\Component\SmsSender\Exception\SendingFailedException $e) {
			return $this->json([
				'ok' => false,
				'message' => sprintf('Sending failed: %s', $e->getMessage()),
			], 503);
		}

		return $this->json(['ok' => true]);
	}

	public function sendEmail(Request $request): Response {
		$responseOrNull = $this->authOrPrepareFailureResponse($request);
		if ($responseOrNull !== null) {
			return $responseOrNull;
		}

		$parts = explode("\n", trim(file_get_contents('php://input')), 3);
		if (count($parts) !== 3) {
			return $this->json([
				'ok' => false,
				'message' => sprintf(
					'Payload needs to contain 3 new-line separated parts: email address, subject, message text (may contain new lines). Got %d parts',
					count($parts),
				),
			], 400);
		}

		list($emailAddress, $subject, $messageText) = $parts;

		if (!$emailAddress) {
			return $this->json(['ok' => false, 'message' => 'Empty email address parameter in body payload'], 422);
		}
		if (!$subject) {
			return $this->json(['ok' => false, 'message' => 'Empty subject parameter in body payload'], 422);
		}
		if (!$messageText) {
			return $this->json(['ok' => false, 'message' => 'Empty message text parameter in body payload'], 422);
		}

		$message = new \Swift_Message();
		$message->setSubject($subject);
		$message->setFrom($this->senderEmailAddress);
		$message->setTo($emailAddress);
		$message->setBody($messageText, 'text/plain');

		$this->getNotificationEmailMailer()->send($message);

		return $this->json(['ok' => true]);
	}

	private function authOrPrepareFailureResponse(Request $request): ?Response {
		$authorization = $request->headers->get('Authorization', '');
		$token = substr($authorization, strlen('Bearer '));

		if ($token === '') {
			return $this->json(['ok' => false, 'message' => 'Missing Authorization Bearer token'], 401);
		}

		if (!StringHelper::equals($this->apiSecret, $token)) {
			return $this->json(['ok' => false, 'message' => 'Bad secret in Authorization Bearer token'], 403);
		}

		return null;
	}

	private function getNotificationSmsGateway(): \Devture\Component\SmsSender\Gateway\GatewayInterface {
		return $this->getNs('notification.sms.gateway');
	}

	private function getNotificationEmailMailer(): \Swift_Mailer {
		return $this->getNs('notification.email.mailer');
	}

}

<?php

namespace Devture\Bundle\NagiosBundle\Controller\Api;

use Devture\Bundle\NagiosBundle\Notification\SmsSender;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Server-to-server notification endpoints invoked by the Nagios notification
 * commands. They authenticate with the shared API secret (Authorization: Bearer)
 * rather than a user session, so they are exposed as PUBLIC_ACCESS in
 * security.yaml and do their own authorization here.
 */
#[Route('/api/notification')]
class NotificationApiController extends AbstractController
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly SmsSender $smsSender,
        #[Autowire('%nagadmin.notifications.api_secret%')] private readonly string $apiSecret,
        #[Autowire('%nagadmin.notifications.sender_email%')] private readonly string $senderEmailAddress,
    ) {
    }

    #[Route('/send-sms', name: 'devture_nagios.api.notification.send_sms', methods: ['POST'])]
    public function sendSms(Request $request): JsonResponse
    {
        $failureResponse = $this->authOrPrepareFailureResponse($request);
        if ($failureResponse !== null) {
            return $failureResponse;
        }

        $parts = explode("\n", trim($request->getContent()), 2);
        if (count($parts) !== 2) {
            return $this->json([
                'ok' => false,
                'message' => sprintf(
                    'Payload needs to contain 2 new-line separated parts: phone number, message text (may contain new lines). Got %d parts',
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

        try {
            $this->smsSender->send($phoneNumber, $messageText);
        } catch (\Throwable $e) {
            return $this->json([
                'ok' => false,
                'message' => sprintf('Sending failed: %s', $e->getMessage()),
            ], 503);
        }

        return $this->json(['ok' => true]);
    }

    #[Route('/send-email', name: 'devture_nagios.api.notification.send_email', methods: ['POST'])]
    public function sendEmail(Request $request): JsonResponse
    {
        $failureResponse = $this->authOrPrepareFailureResponse($request);
        if ($failureResponse !== null) {
            return $failureResponse;
        }

        $parts = explode("\n", trim($request->getContent()), 3);
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

        $message = (new Email())
            ->from($this->senderEmailAddress)
            ->to($emailAddress)
            ->subject($subject)
            ->text($messageText);

        $this->mailer->send($message);

        return $this->json(['ok' => true]);
    }

    private function authOrPrepareFailureResponse(Request $request): ?JsonResponse
    {
        $authorization = $request->headers->get('Authorization', '') ?? '';
        $token = substr($authorization, strlen('Bearer '));

        if ($token === '') {
            return $this->json(['ok' => false, 'message' => 'Missing Authorization Bearer token'], 401);
        }

        if (!hash_equals($this->apiSecret, $token)) {
            return $this->json(['ok' => false, 'message' => 'Bad secret in Authorization Bearer token'], 403);
        }

        return null;
    }
}

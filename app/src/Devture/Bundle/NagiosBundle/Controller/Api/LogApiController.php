<?php

namespace Devture\Bundle\NagiosBundle\Controller\Api;

use Devture\Bundle\NagiosBundle\ApiModelBridge\LogBridge;
use Devture\Bundle\NagiosBundle\Helper\AccessChecker;
use Devture\Bundle\NagiosBundle\Helper\CurrentUserProvider;
use Devture\Bundle\NagiosBundle\Log\Fetcher as LogFetcher;
use Devture\Bundle\NagiosBundle\Log\LogEntry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class LogApiController extends AbstractController
{
    public function __construct(
        private readonly LogFetcher $logFetcher,
        private readonly AccessChecker $accessChecker,
        private readonly CurrentUserProvider $currentUserProvider,
        private readonly LogBridge $logBridge,
    ) {
    }

    #[Route('/logs/{ifNewerThanId}', name: 'devture_nagios.api.log.list', methods: ['GET'], defaults: ['ifNewerThanId' => null])]
    public function list(?string $ifNewerThanId): JsonResponse
    {
        $items = $this->logFetcher->fetch();

        if ($ifNewerThanId !== null && count($items) > 0) {
            if ($items[0]->getId() === $ifNewerThanId) {
                $items = [];
            }
        }

        $user = $this->currentUserProvider->getUser();

        $items = array_values(array_filter(
            $items,
            fn (LogEntry $logEntry) => $this->accessChecker->canUserViewLogEntry($user, $logEntry),
        ));

        $result = array_map(fn (LogEntry $entity) => $this->logBridge->export($entity), $items);

        return $this->json($result);
    }
}

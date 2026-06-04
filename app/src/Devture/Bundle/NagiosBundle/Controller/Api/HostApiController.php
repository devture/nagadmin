<?php

namespace Devture\Bundle\NagiosBundle\Controller\Api;

use Devture\Bundle\NagiosBundle\ApiModelBridge\HostInfoBridge;
use Devture\Bundle\NagiosBundle\Helper\AccessChecker;
use Devture\Bundle\NagiosBundle\Helper\CurrentUserProvider;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Model\HostInfo;
use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Model\ServiceInfo;
use Devture\Bundle\NagiosBundle\NagiosCommand\Manager as NagiosCommandManager;
use Devture\Bundle\NagiosBundle\Repository\HostRepository;
use Devture\Bundle\NagiosBundle\Repository\ServiceRepository;
use Devture\Bundle\NagiosBundle\Status\Manager as StatusManager;
use Devture\Bundle\NagiosBundle\Status\ServiceStatus;
use Devture\Component\DBAL\Exception\NotFound;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/api')]
class HostApiController extends AbstractController
{
    public function __construct(
        private readonly HostRepository $hostRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly StatusManager $statusManager,
        private readonly AccessChecker $accessChecker,
        private readonly CurrentUserProvider $currentUserProvider,
        private readonly HostInfoBridge $hostInfoBridge,
        private readonly NagiosCommandManager $nagiosCommandManager,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/hosts-info/{id}', name: 'devture_nagios.api.host.info', methods: ['GET'], defaults: ['id' => null])]
    public function info(?string $id): JsonResponse
    {
        $user = $this->currentUserProvider->getUser();

        $selectedHost = null;
        try {
            if ($id) {
                $selectedHost = $this->hostRepository->find($id);

                if (!$this->accessChecker->canUserViewHost($user, $selectedHost)) {
                    throw $this->createAccessDeniedException();
                }
            }
        } catch (NotFound) {
            return $this->json([]);
        }

        $hosts = $this->hostRepository->findBy([], ['sort' => ['name' => 1]]);

        $items = [];
        foreach ($hosts as $host) {
            if ($selectedHost === null || $selectedHost === $host) {
                if (!$this->accessChecker->canUserViewHost($user, $host)) {
                    continue;
                }
                $items[] = $this->createHostInfo($host);
            }
        }

        if ($selectedHost === null) {
            $result = array_map(fn (HostInfo $entity) => $this->hostInfoBridge->export($entity), $items);
        } else {
            $result = $this->hostInfoBridge->export($items[0]);
        }

        return $this->json($result);
    }

    #[Route(
        '/host/recheck-services/{id}/{recheckType}/{token}',
        name: 'devture_nagios.api.host.recheck_services',
        methods: ['POST'],
        requirements: ['recheckType' => 'all|failing|__RECHECK_TYPE__'],
    )]
    public function recheckServices(string $id, string $recheckType, string $token): JsonResponse
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('nagadmin', $token))) {
            return $this->json(['ok' => false, 'unauthorized' => true]);
        }

        if ($recheckType === '__RECHECK_TYPE__') {
            $recheckType = 'all';
        }

        $scheduledCount = 0;
        try {
            $host = $this->hostRepository->find($id);

            if (!$this->accessChecker->canUserViewHost($this->currentUserProvider->getUser(), $host)) {
                return $this->json(['ok' => false, 'unauthorized' => true]);
            }

            foreach ($this->serviceRepository->findByHost($host) as $service) {
                if ($this->shouldRecheckService($service, $recheckType)) {
                    $scheduledCount += 1;
                    $this->nagiosCommandManager->scheduleServiceCheck($service);
                }
            }
        } catch (NotFound) {
            // Host gone — nothing to recheck.
        }

        return $this->json(['ok' => true, 'scheduledCount' => $scheduledCount]);
    }

    private function createHostInfo(Host $host): HostInfo
    {
        $services = $this->serviceRepository->findByHost($host);

        $servicesInfo = array_map(function (Service $service) {
            $serviceStatus = $this->statusManager->getServiceStatus($service);

            return new ServiceInfo($service, $serviceStatus);
        }, $services);

        return new HostInfo($host, $servicesInfo);
    }

    private function shouldRecheckService(Service $service, string $recheckType): bool
    {
        if ($recheckType === 'all') {
            return true;
        }

        if ($recheckType === 'failing') {
            $status = $this->statusManager->getServiceStatus($service);
            if ($status === null) {
                return false;
            }
            if (!$status->isChecked()) {
                return true;
            }

            return $status->getCurrentState() !== ServiceStatus::STATUS_OK;
        }

        throw new \InvalidArgumentException('Unknown recheck type: ' . $recheckType);
    }
}

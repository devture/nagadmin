<?php

namespace Devture\Bundle\NagiosBundle\Controller;

use Devture\Bundle\NagiosBundle\Deployment\Deployer;
use Devture\Bundle\NagiosBundle\Form\ServiceFormBinder;
use Devture\Bundle\NagiosBundle\Log\Fetcher as LogFetcher;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\NagiosCommand\Manager as NagiosCommandManager;
use Devture\Bundle\NagiosBundle\Repository\CommandRepository;
use Devture\Bundle\NagiosBundle\Repository\ContactRepository;
use Devture\Bundle\NagiosBundle\Repository\HostRepository;
use Devture\Bundle\NagiosBundle\Repository\ServiceRepository;
use Devture\Bundle\NagiosBundle\Security\Voter\NagiosAccessVoter;
use Devture\Component\DBAL\Exception\NotFound;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/service')]
class ServiceManagementController extends AbstractController
{
    public function __construct(
        private readonly ServiceRepository $repository,
        private readonly HostRepository $hostRepository,
        private readonly CommandRepository $commandRepository,
        private readonly ContactRepository $contactRepository,
        private readonly ServiceFormBinder $formBinder,
        private readonly NagiosCommandManager $nagiosCommandManager,
        private readonly LogFetcher $logFetcher,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly Deployer $deployer,
        #[Autowire('%nagadmin.service.defaults%')]
        private readonly array $defaults,
    ) {
    }

    #[Route('/manage', name: 'devture_nagios.service.manage', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('@DevtureNagios/service/index.html.twig');
    }

    #[Route('/add/{hostId}/{commandId}', name: 'devture_nagios.service.add', methods: ['GET', 'POST'])]
    public function add(Request $request, string $hostId, string $commandId): Response
    {
        $entity = $this->repository->createModel([]);

        $entity->setMaxCheckAttempts($this->defaults['max_check_attempts']);
        $entity->setCheckInterval($this->defaults['check_interval']);
        $entity->setRetryInterval($this->defaults['retry_interval']);
        $entity->setNotificationInterval($this->defaults['notification_interval']);

        try {
            $command = $this->commandRepository->find($commandId);
            if ($command->getType() !== Command::TYPE_SERVICE_CHECK) {
                throw new NotFound('Only service check commands are allowed.');
            }
            $entity->setCommand($command);
            $entity->setName($command->getTitle());
        } catch (NotFound) {
            throw $this->createNotFoundException();
        }

        try {
            $host = $this->hostRepository->find($hostId);
        } catch (NotFound) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('ROLE_CONFIGURATION_MANAGEMENT');
        $entity->setHost($host);

        if ($request->getMethod() === 'POST' && $this->formBinder->bind($entity, $request)) {
            $this->repository->add($entity);
            $this->deployer->tryDeploy();

            return $this->redirect($request->query->get('next', $this->generateUrl('devture_nagios.service.manage')));
        }

        return $this->render('@DevtureNagios/service/record.html.twig', array_merge($this->getBaseViewData(), [
            'entity' => $entity,
            'isAdded' => false,
            'form' => $this->formBinder,
        ]));
    }

    #[Route('/edit/{id}', name: 'devture_nagios.service.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $id): Response
    {
        try {
            $entity = $this->repository->find($id);
        } catch (NotFound) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('ROLE_CONFIGURATION_MANAGEMENT');

        if ($request->getMethod() === 'POST' && $this->formBinder->bind($entity, $request)) {
            $this->repository->update($entity);
            $this->deployer->tryDeploy();

            return $this->redirect($request->query->get('next', $this->generateUrl('devture_nagios.service.manage')));
        }

        return $this->render('@DevtureNagios/service/record.html.twig', array_merge($this->getBaseViewData(), [
            'entity' => $entity,
            'isAdded' => true,
            'form' => $this->formBinder,
        ]));
    }

    #[Route('/view/{id}', name: 'devture_nagios.service.view', methods: ['GET'])]
    public function view(string $id): Response
    {
        try {
            $entity = $this->repository->find($id);
        } catch (NotFound) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(NagiosAccessVoter::VIEW, $entity);

        return $this->render('@DevtureNagios/service/view.html.twig', [
            'entity' => $entity,
            'logs' => $this->logFetcher->fetchForService($entity),
        ]);
    }

    #[Route('/schedule_check/{id}/{token}', name: 'devture_nagios.service.schedule_check', methods: ['POST'])]
    public function scheduleCheck(string $id, string $token): JsonResponse
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('schedule-service-check-' . $id, $token))) {
            return $this->json(['ok' => false]);
        }

        if (!$this->isGranted('ROLE_CONFIGURATION_MANAGEMENT')) {
            return $this->json(['ok' => false]);
        }

        try {
            $service = $this->repository->find($id);
            $this->nagiosCommandManager->scheduleServiceCheck($service);
        } catch (NotFound) {
            // Already gone — nothing to schedule.
        }

        return $this->json(['ok' => true]);
    }

    #[Route('/delete/{id}/{token}', name: 'devture_nagios.service.delete', methods: ['POST'])]
    public function delete(string $id, string $token): JsonResponse
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete-service-' . $id, $token))) {
            return $this->json(['ok' => false]);
        }

        if (!$this->isGranted('ROLE_CONFIGURATION_MANAGEMENT')) {
            return $this->json(['ok' => false]);
        }

        try {
            $service = $this->repository->find($id);
            $this->repository->delete($service);
            $this->deployer->tryDeploy();
        } catch (NotFound) {
            // Already gone — treat as success.
        }

        return $this->json(['ok' => true]);
    }

    private function getBaseViewData(): array
    {
        return [
            'contacts' => $this->contactRepository->findBy([], ['sort' => ['name' => 1]]),
        ];
    }
}

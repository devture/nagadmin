<?php

namespace Devture\Bundle\NagiosBundle\Controller;

use Devture\Bundle\NagiosBundle\Deployment\Deployer;
use Devture\Bundle\NagiosBundle\Form\HostFormBinder;
use Devture\Bundle\NagiosBundle\Helper\AccessChecker;
use Devture\Bundle\NagiosBundle\Helper\CurrentUserProvider;
use Devture\Bundle\NagiosBundle\Log\Fetcher as LogFetcher;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Repository\CommandRepository;
use Devture\Bundle\NagiosBundle\Repository\HostRepository;
use Devture\Bundle\NagiosBundle\Repository\ServiceRepository;
use Devture\Component\DBAL\Exception\NotFound;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/host')]
class HostManagementController extends AbstractController
{
    public function __construct(
        private readonly HostRepository $repository,
        private readonly ServiceRepository $serviceRepository,
        private readonly CommandRepository $commandRepository,
        private readonly HostFormBinder $formBinder,
        private readonly LogFetcher $logFetcher,
        private readonly AccessChecker $accessChecker,
        private readonly CurrentUserProvider $currentUserProvider,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly Deployer $deployer,
    ) {
    }

    #[Route('/manage', name: 'devture_nagios.host.manage', methods: ['GET'])]
    public function index(): Response
    {
        $items = $this->repository->findBy([], ['sort' => ['name' => 1]]);

        return $this->render('@DevtureNagios/host/index.html.twig', ['items' => $items]);
    }

    #[Route('/add', name: 'devture_nagios.host.add', methods: ['GET', 'POST'])]
    public function add(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CONFIGURATION_MANAGEMENT');

        $entity = $this->repository->createModel([]);

        if ($request->getMethod() === 'POST' && $this->formBinder->bind($entity, $request)) {
            $this->repository->add($entity);

            return $this->redirectToRoute('devture_nagios.host.manage');
        }

        return $this->render('@DevtureNagios/host/record.html.twig', array_merge($this->getBaseViewData($entity), [
            'entity' => $entity,
            'isAdded' => false,
            'form' => $this->formBinder,
        ]));
    }

    #[Route('/edit/{id}', name: 'devture_nagios.host.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CONFIGURATION_MANAGEMENT');

        try {
            $entity = $this->repository->find($id);
        } catch (NotFound) {
            throw $this->createNotFoundException();
        }

        if ($request->getMethod() === 'POST' && $this->formBinder->bind($entity, $request)) {
            $this->repository->update($entity);
            $this->deployer->tryDeploy();

            return $this->redirectToRoute('devture_nagios.host.manage');
        }

        return $this->render('@DevtureNagios/host/record.html.twig', array_merge($this->getBaseViewData($entity), [
            'entity' => $entity,
            'isAdded' => true,
            'form' => $this->formBinder,
        ]));
    }

    #[Route('/view/{id}', name: 'devture_nagios.host.view', methods: ['GET'])]
    public function view(string $id): Response
    {
        try {
            $entity = $this->repository->find($id);
        } catch (NotFound) {
            throw $this->createNotFoundException();
        }

        if (!$this->accessChecker->canUserViewHost($this->currentUserProvider->getUser(), $entity)) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('@DevtureNagios/host/view.html.twig', [
            'entity' => $entity,
            'logs' => $this->logFetcher->fetchForHost($entity),
        ]);
    }

    #[Route('/delete/{id}/{token}', name: 'devture_nagios.host.delete', methods: ['POST'])]
    public function delete(string $id, string $token): JsonResponse
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete-host-' . $id, $token))) {
            return $this->json(['ok' => false]);
        }

        if (!$this->isGranted('ROLE_CONFIGURATION_MANAGEMENT')) {
            return $this->json(['ok' => false]);
        }

        try {
            $host = $this->repository->find($id);
            $this->repository->delete($host);
            $this->deployer->tryDeploy();
        } catch (NotFound) {
            // Already gone — treat as success.
        }

        return $this->json(['ok' => true]);
    }

    private function getBaseViewData($currentHost): array
    {
        $groups = array_unique(array_merge($this->repository->getDistinctGroups(), $currentHost->getGroups()));

        return [
            'groups' => $groups,
            'services' => $this->serviceRepository->findByHost($currentHost),
            'commands' => $this->commandRepository->findBy(['type' => Command::TYPE_SERVICE_CHECK], ['sort' => ['title' => 1]]),
        ];
    }
}

<?php

namespace Devture\Bundle\NagiosBundle\Controller;

use Devture\Bundle\NagiosBundle\Deployment\Deployer;
use Devture\Bundle\NagiosBundle\Form\CommandFormBinder;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Repository\CommandRepository;
use Devture\Bundle\NagiosBundle\Repository\ContactRepository;
use Devture\Bundle\NagiosBundle\Repository\ServiceRepository;
use Devture\Component\DBAL\Exception\NotFound;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/command')]
class CommandManagementController extends AbstractController
{
    public function __construct(
        private readonly CommandRepository $repository,
        private readonly ServiceRepository $serviceRepository,
        private readonly ContactRepository $contactRepository,
        private readonly CommandFormBinder $formBinder,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly Deployer $deployer,
    ) {
    }

    #[Route('/manage/{type}', name: 'devture_nagios.command.manage', defaults: ['type' => Command::TYPE_SERVICE_CHECK], methods: ['GET'])]
    public function index(string $type): Response
    {
        if (!in_array($type, Command::getTypes(), true)) {
            throw $this->createNotFoundException();
        }

        $items = $this->repository->findBy(['type' => $type], ['sort' => ['name' => 1]]);

        return $this->render('@DevtureNagios/command/index.html.twig', ['items' => $items, 'type' => $type]);
    }

    #[Route('/add/{type}', name: 'devture_nagios.command.add', methods: ['GET', 'POST'])]
    public function add(Request $request, string $type): Response
    {
        $entity = $this->repository->createModel([]);
        $entity->setType($type);

        if ($request->getMethod() === 'POST' && $this->formBinder->bind($entity, $request)) {
            $this->repository->add($entity);

            return $this->redirectToRoute('devture_nagios.command.manage', ['type' => $entity->getType()]);
        }

        return $this->render('@DevtureNagios/command/record.html.twig', [
            'entity' => $entity,
            'isAdded' => false,
            'isUsed' => false,
            'form' => $this->formBinder,
        ]);
    }

    #[Route('/edit/{id}', name: 'devture_nagios.command.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $id): Response
    {
        try {
            $entity = $this->repository->find($id);
        } catch (NotFound) {
            throw $this->createNotFoundException();
        }

        if ($request->getMethod() === 'POST' && $this->formBinder->bind($entity, $request)) {
            $this->repository->update($entity);
            $this->deployer->tryDeploy();

            return $this->redirectToRoute('devture_nagios.command.manage', ['type' => $entity->getType()]);
        }

        return $this->render('@DevtureNagios/command/record.html.twig', [
            'entity' => $entity,
            'isAdded' => true,
            'isUsed' => $this->isCommandUsed($entity),
            'form' => $this->formBinder,
        ]);
    }

    #[Route('/delete/{id}/{token}', name: 'devture_nagios.command.delete', methods: ['POST'])]
    public function delete(string $id, string $token): JsonResponse
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete-command-' . $id, $token))) {
            return $this->json(['ok' => false]);
        }

        try {
            $command = $this->repository->find($id);
            if ($this->isCommandUsed($command)) {
                return $this->json(['ok' => false]);
            }

            $this->repository->delete($command);
            $this->deployer->tryDeploy();
        } catch (NotFound) {
            // Already gone — treat as success.
        }

        return $this->json(['ok' => true]);
    }

    private function isCommandUsed(Command $entity): bool
    {
        if ($entity->getType() === Command::TYPE_SERVICE_CHECK) {
            return count($this->serviceRepository->findByCommand($entity)) !== 0;
        }

        if ($entity->getType() === Command::TYPE_SERVICE_NOTIFICATION) {
            return count($this->contactRepository->findByCommand($entity)) !== 0;
        }

        throw new \LogicException('Unknown command type: ' . $entity->getType());
    }
}

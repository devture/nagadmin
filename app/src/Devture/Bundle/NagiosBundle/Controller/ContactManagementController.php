<?php

namespace Devture\Bundle\NagiosBundle\Controller;

use Devture\Bundle\NagiosBundle\Deployment\Deployer;
use Devture\Bundle\NagiosBundle\Form\ContactFormBinder;
use Devture\Bundle\NagiosBundle\Helper\AccessChecker;
use Devture\Bundle\NagiosBundle\Helper\CurrentUserProvider;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Model\Contact;
use Devture\Bundle\NagiosBundle\Repository\CommandRepository;
use Devture\Bundle\NagiosBundle\Repository\ContactRepository;
use Devture\Bundle\NagiosBundle\Repository\TimePeriodRepository;
use Devture\Bundle\UserBundle\Repository\UserRepositoryInterface;
use Devture\Component\DBAL\Exception\NotFound;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/contact')]
class ContactManagementController extends AbstractController
{
    public function __construct(
        private readonly ContactRepository $repository,
        private readonly TimePeriodRepository $timePeriodRepository,
        private readonly CommandRepository $commandRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly ContactFormBinder $formBinder,
        private readonly AccessChecker $accessChecker,
        private readonly CurrentUserProvider $currentUserProvider,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly Deployer $deployer,
    ) {
    }

    #[Route('/manage', name: 'devture_nagios.contact.manage', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->currentUserProvider->getUser();
        if ($user !== null && !$this->accessChecker->canUserDoConfigurationManagement($user)) {
            $criteria = ['userId' => $user->getId()];
        } else {
            $criteria = [];
        }
        $items = $this->repository->findBy($criteria, ['sort' => ['name' => 1]]);

        return $this->render('@DevtureNagios/contact/index.html.twig', ['items' => $items]);
    }

    #[Route('/add', name: 'devture_nagios.contact.add', methods: ['GET', 'POST'])]
    public function add(Request $request): Response
    {
        $user = $this->currentUserProvider->getUser();
        if (!$this->accessChecker->canUserCreateContacts($user)) {
            throw $this->createAccessDeniedException();
        }

        $entity = $this->repository->createModel([]);

        if (!$this->accessChecker->canUserDoConfigurationManagement($user)) {
            $entity->setUser($user);
        }

        if ($request->getMethod() === 'POST' && $this->formBinder->bind($entity, $request)) {
            $this->repository->add($entity);

            return $this->redirectToRoute('devture_nagios.contact.manage');
        }

        return $this->render('@DevtureNagios/contact/record.html.twig', array_merge($this->getBaseViewData(), [
            'entity' => $entity,
            'isAdded' => false,
            'form' => $this->formBinder,
        ]));
    }

    #[Route('/edit/{id}', name: 'devture_nagios.contact.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $id): Response
    {
        try {
            $entity = $this->repository->find($id);
        } catch (NotFound) {
            throw $this->createNotFoundException();
        }

        if (!$this->accessChecker->canUserManageContact($this->currentUserProvider->getUser(), $entity)) {
            throw $this->createAccessDeniedException();
        }

        if ($request->getMethod() === 'POST' && $this->formBinder->bind($entity, $request)) {
            $this->repository->update($entity);
            $this->deployer->tryDeploy();

            return $this->redirectToRoute('devture_nagios.contact.manage');
        }

        return $this->render('@DevtureNagios/contact/record.html.twig', array_merge($this->getBaseViewData(), [
            'entity' => $entity,
            'isAdded' => true,
            'form' => $this->formBinder,
        ]));
    }

    #[Route('/delete/{id}/{token}', name: 'devture_nagios.contact.delete', methods: ['POST'])]
    public function delete(string $id, string $token): JsonResponse
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete-contact-' . $id, $token))) {
            return $this->json(['ok' => false]);
        }

        try {
            $contact = $this->repository->find($id);
            if (!$this->accessChecker->canUserManageContact($this->currentUserProvider->getUser(), $contact)) {
                return $this->json(['ok' => false]);
            }

            $this->repository->delete($contact);
            $this->deployer->tryDeploy();
        } catch (NotFound) {
            // Already gone — treat as success.
        }

        return $this->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getBaseViewData(): array
    {
        return [
            'timePeriods' => $this->timePeriodRepository->findBy([], ['sort' => ['title' => 1]]),
            'notificationCommands' => $this->commandRepository->findBy(['type' => Command::TYPE_SERVICE_NOTIFICATION], ['sort' => ['title' => 1]]),
            'addressSlotsCount' => Contact::ADDRESS_SLOTS_COUNT,
            'users' => $this->userRepository->findAll(),
        ];
    }
}

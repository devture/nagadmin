<?php

namespace Devture\Bundle\UserBundle\Controller;

use Devture\Bundle\UserBundle\Form\UserFormBinder;
use Devture\Bundle\UserBundle\Repository\UserRepositoryInterface;
use Devture\Component\DBAL\Exception\NotFound;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/user')]
class UserController extends AbstractController
{
    /**
     * @param array<string, string> $roles
     */
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly UserFormBinder $formBinder,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        #[Autowire('%nagadmin.user.roles%')]
        private readonly array $roles,
    ) {
    }

    #[Route('/manage', name: 'devture_user.manage', methods: ['GET'])]
    public function manage(): Response
    {
        $this->repository->ensureIndexes();

        return $this->render('@DevtureUser/index.html.twig', [
            'items' => $this->repository->findAll(),
        ]);
    }

    #[Route('/add', name: 'devture_user.add', methods: ['GET', 'POST'])]
    public function add(Request $request): Response
    {
        $entity = $this->repository->createModel([]);

        if ($request->getMethod() === 'POST' && $this->formBinder->bind($entity, $request)) {
            $this->repository->add($entity);

            return $this->redirectToRoute('devture_user.manage');
        }

        return $this->render('@DevtureUser/record.html.twig', [
            'entity' => $entity,
            'isAdded' => false,
            'form' => $this->formBinder,
            'roles' => $this->roles,
        ]);
    }

    #[Route('/edit/{id}', name: 'devture_user.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $id): Response
    {
        try {
            $entity = $this->repository->find($id);
        } catch (NotFound) {
            throw $this->createNotFoundException();
        }

        if ($request->getMethod() === 'POST' && $this->formBinder->bind($entity, $request)) {
            $this->repository->update($entity);

            return $this->redirectToRoute('devture_user.manage');
        }

        return $this->render('@DevtureUser/record.html.twig', [
            'entity' => $entity,
            'isAdded' => true,
            'form' => $this->formBinder,
            'roles' => $this->roles,
        ]);
    }

    #[Route('/_delete/{id}/{token}', name: 'devture_user.delete', methods: ['POST'])]
    public function delete(string $id, string $token): JsonResponse
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete-user-' . $id, $token))) {
            return $this->json(['ok' => false]);
        }

        try {
            $this->repository->delete($this->repository->find($id));
        } catch (NotFound) {
            // Already gone — treat as success.
        }

        return $this->json(['ok' => true]);
    }
}

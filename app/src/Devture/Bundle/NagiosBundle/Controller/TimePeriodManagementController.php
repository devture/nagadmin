<?php

namespace Devture\Bundle\NagiosBundle\Controller;

use Devture\Bundle\NagiosBundle\Deployment\Deployer;
use Devture\Bundle\NagiosBundle\Form\TimePeriodFormBinder;
use Devture\Bundle\NagiosBundle\Model\TimePeriod;
use Devture\Bundle\NagiosBundle\Repository\ContactRepository;
use Devture\Bundle\NagiosBundle\Repository\TimePeriodRepository;
use Devture\Component\DBAL\Exception\NotFound;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/time-period')]
class TimePeriodManagementController extends AbstractController
{
	public function __construct(
		private readonly TimePeriodRepository $repository,
		private readonly ContactRepository $contactRepository,
		private readonly TimePeriodFormBinder $formBinder,
		private readonly CsrfTokenManagerInterface $csrfTokenManager,
		private readonly Deployer $deployer,
	) {
	}

	#[Route('/manage', name: 'devture_nagios.time_period.manage', methods: ['GET'])]
	public function index(): Response
	{
		$items = $this->repository->findBy([], ['sort' => ['title' => 1]]);

		return $this->render('@DevtureNagios/time_period/index.html.twig', ['items' => $items]);
	}

	#[Route('/add', name: 'devture_nagios.time_period.add', methods: ['GET', 'POST'])]
	public function add(Request $request): Response
	{
		$entity = $this->repository->createModel([]);

		if ($request->getMethod() === 'POST' && $this->formBinder->bind($entity, $request)) {
			$this->repository->add($entity);

			return $this->redirectToRoute('devture_nagios.time_period.manage');
		}

		return $this->render('@DevtureNagios/time_period/record.html.twig', [
			'entity' => $entity,
			'isAdded' => false,
			'isUsed' => false,
			'form' => $this->formBinder,
		]);
	}

	#[Route('/edit/{id}', name: 'devture_nagios.time_period.edit', methods: ['GET', 'POST'])]
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

			return $this->redirectToRoute('devture_nagios.time_period.manage');
		}

		return $this->render('@DevtureNagios/time_period/record.html.twig', [
			'entity' => $entity,
			'isAdded' => true,
			'isUsed' => $this->isTimePeriodUsed($entity),
			'form' => $this->formBinder,
		]);
	}

	#[Route('/delete/{id}/{token}', name: 'devture_nagios.time_period.delete', methods: ['POST'])]
	public function delete(string $id, string $token): JsonResponse
	{
		if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete-time-period-' . $id, $token))) {
			return $this->json(['ok' => false]);
		}

		try {
			$timePeriod = $this->repository->find($id);
			if ($this->isTimePeriodUsed($timePeriod)) {
				return $this->json(['ok' => false]);
			}

			$this->repository->delete($timePeriod);
			$this->deployer->tryDeploy();
		} catch (NotFound) {
			// Already gone — treat as success.
		}

		return $this->json(['ok' => true]);
	}

	private function isTimePeriodUsed(TimePeriod $entity): bool
	{
		return count($this->contactRepository->findByTimePeriod($entity)) !== 0;
	}
}

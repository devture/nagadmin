<?php

namespace Devture\Bundle\NagiosBundle\Controller;

use Devture\Bundle\NagiosBundle\Deployment\Deployer;
use Devture\Bundle\NagiosBundle\Form\ResourceFormBinder;
use Devture\Bundle\NagiosBundle\Model\Resource;
use Devture\Bundle\NagiosBundle\Repository\ResourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/resource')]
class ResourceManagementController extends AbstractController
{
	public function __construct(
		private readonly ResourceRepository $repository,
		private readonly ResourceFormBinder $formBinder,
		private readonly Deployer $deployer,
	) {
	}

	#[Route('/manage', name: 'devture_nagios.resource.manage', methods: ['GET', 'POST'])]
	public function manage(Request $request): Response
	{
		$entity = $this->repository->getResource();

		if ($request->getMethod() === 'POST' && $this->formBinder->bind($entity, $request)) {
			$this->repository->update($entity);
			$this->deployer->tryDeploy();

			return $this->redirectToRoute('devture_nagios.resource.manage');
		}

		return $this->render('@DevtureNagios/resource/management.html.twig', [
			'userVariablesCount' => Resource::USER_VARIABLES_COUNT,
			'entity' => $entity,
			'form' => $this->formBinder,
		]);
	}
}

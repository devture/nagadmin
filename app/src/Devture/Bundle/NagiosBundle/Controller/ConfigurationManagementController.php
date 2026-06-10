<?php

namespace Devture\Bundle\NagiosBundle\Controller;

use Devture\Bundle\NagiosBundle\Deployment\ConfigurationCollector;
use Devture\Bundle\NagiosBundle\Deployment\ConfigurationTester;
use Devture\Bundle\NagiosBundle\Deployment\Handler\DeploymentHandlerInterface;
use Devture\Bundle\NagiosBundle\Exception\DeploymentFailedException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/configuration')]
class ConfigurationManagementController extends AbstractController
{
	public function __construct(
		private readonly ConfigurationCollector $collector,
		private readonly ConfigurationTester $tester,
		private readonly DeploymentHandlerInterface $deploymentHandler,
		private readonly CsrfTokenManagerInterface $csrfTokenManager,
	) {
	}

	#[Route('/test', name: 'devture_nagios.configuration.test', methods: ['GET'])]
	public function test(): Response
	{
		[$files, $isValid, $checkOutput] = $this->getTestedConfiguration();

		return $this->render('@DevtureNagios/configuration/test.html.twig', [
			'files' => $files,
			'isValid' => $isValid,
			'checkOutput' => $checkOutput,
		]);
	}

	#[Route('/deploy', name: 'devture_nagios.configuration.deploy', methods: ['POST'])]
	public function deploy(Request $request): Response
	{
		if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('deploy', (string) $request->request->get('token')))) {
			throw $this->createAccessDeniedException();
		}

		[$files, $isValid, $checkOutput] = $this->getTestedConfiguration();

		if (!$isValid) {
			return $this->render('@DevtureNagios/configuration/test.html.twig', [
				'files' => $files,
				'isValid' => $isValid,
				'checkOutput' => $checkOutput,
			]);
		}

		$error = null;
		try {
			$this->deploymentHandler->deploy($files);
		} catch (DeploymentFailedException $e) {
			$error = $e->getMessage();
		}

		return $this->render('@DevtureNagios/configuration/deploy.html.twig', ['error' => $error]);
	}

	/**
	 * @return array{0: list<\Devture\Bundle\NagiosBundle\Deployment\ConfigurationFile>, 1: bool, 2: string}
	 */
	private function getTestedConfiguration(): array
	{
		$files = $this->collector->collect();
		[$isValid, $checkOutput] = $this->tester->test($files);

		return [$files, $isValid, $checkOutput];
	}
}

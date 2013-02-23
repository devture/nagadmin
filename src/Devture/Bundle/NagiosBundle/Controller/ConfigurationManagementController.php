<?php
namespace Devture\Bundle\NagiosBundle\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Devture\Bundle\SharedBundle\Exception\NotFound;
use Devture\Bundle\SharedBundle\Controller\BaseController;
use Devture\Bundle\NagiosBundle\Exception\DeploymentFailedException;

class ConfigurationManagementController extends BaseController {

	private function getTestedConfiguration() {
		$files = $this->getNs('deployment.configuration_collector')->collect();
		list($isValid, $checkOutput) = $this->getNs('deployment.configuration_tester')->test($files);
		return array($files, $isValid, $checkOutput);
	}

	public function testAction() {
		list($files, $isValid, $checkOutput) = $this->getTestedConfiguration();
		$viewData = array('files' => $files, 'isValid' => $isValid, 'checkOutput' => $checkOutput);
		return $this->renderView('DevtureNagiosBundle/configuration/test.html.twig', $viewData);
	}

	public function deployAction(Request $request) {
		if (!$this->get('shared.csrf_token_generator')->isValid('deploy', $request->request->get('token'))) {
			return $this->abort(401);
		}

		list($files, $isValid, $checkOutput) = $this->getTestedConfiguration();

		if (!$isValid) {
			$viewData = array('files' => $files, 'isValid' => $isValid, 'checkOutput' => $checkOutput);
			return $this->renderView('configuration.test', $viewData);
		}

		$viewData = array('error' => null);
		try {
			$this->getNs('deployment.handler')->deploy($files);
		} catch (DeploymentFailedException $e) {
			$viewData['error'] = $e->getMessage();
		}
		return $this->renderView('DevtureNagiosBundle/configuration/deploy.html.twig', $viewData);
	}

}
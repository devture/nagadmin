<?php
namespace Devture\Bundle\NagiosBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Exception\DeploymentFailedException;

class ConfigurationManagementController extends BaseController {

	private function getTestedConfiguration() {
		$files = $this->getDeploymentConfigurationCollector()->collect();
		list($isValid, $checkOutput) = $this->getDeploymentConfigurationTester()->test($files);
		return array($files, $isValid, $checkOutput);
	}

	public function testAction() {
		list($files, $isValid, $checkOutput) = $this->getTestedConfiguration();
		$viewData = array('files' => $files, 'isValid' => $isValid, 'checkOutput' => $checkOutput);
		return $this->renderView('DevtureNagiosBundle/configuration/test.html.twig', $viewData);
	}

	public function deployAction(Request $request) {
		if (!$this->isValidCsrfToken('deploy', $request->request->get('token'))) {
			return $this->abort(401);
		}

		list($files, $isValid, $checkOutput) = $this->getTestedConfiguration();

		if (!$isValid) {
			$viewData = array('files' => $files, 'isValid' => $isValid, 'checkOutput' => $checkOutput);
			return $this->renderView('configuration.test', $viewData);
		}

		$viewData = array('error' => null);
		try {
			$this->getDeploymentHandler()->deploy($files);
		} catch (DeploymentFailedException $e) {
			$viewData['error'] = $e->getMessage();
		}
		return $this->renderView('DevtureNagiosBundle/configuration/deploy.html.twig', $viewData);
	}

}

<?php
namespace Devture\Bundle\NagiosBundle\Controller;

class BaseController extends \Devture\Bundle\SharedBundle\Controller\BaseController {

	/**
	 * @return \Devture\Bundle\NagiosBundle\Repository\HostRepository
	 */
	protected function getHostRepository() {
		return $this->getNs('host.repository');
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Repository\ServiceRepository
	 */
	protected function getServiceRepository() {
		return $this->getNs('service.repository');
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Repository\CommandRepository
	 */
	protected function getCommandRepository() {
		return $this->getNs('command.repository');
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Repository\ContactRepository
	 */
	protected function getContactRepository() {
		return $this->getNs('contact.repository');
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Repository\ResourceRepository
	 */
	protected function getResourceRepository() {
		return $this->getNs('resource.repository');
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Repository\TimePeriodRepository
	 */
	protected function getTimePeriodRepository() {
		return $this->getNs('time_period.repository');
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Deployment\ConfigurationCollector
	 */
	protected function getDeploymentConfigurationCollector() {
		return $this->getNs('deployment.configuration_collector');
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Deployment\ConfigurationTester
	 */
	protected function getDeploymentConfigurationTester() {
		return $this->getNs('deployment.configuration_tester');
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Deployment\Handler\DeploymentHandlerInterface
	 */
	protected function getDeploymentHandler() {
		return $this->getNs('deployment.handler');
	}

	protected function tryDeployConfiguration() {
		$files = $this->getDeploymentConfigurationCollector()->collect();
		list($isValid, $_checkOutput) = $this->getDeploymentConfigurationTester()->test($files);
		if ($isValid) {
			$this->getDeploymentHandler()->deploy($files);
		}
	}

	protected function isValidCsrfToken($intention, $token) {
		return $this->getCsrfTokenGenerator()->isValid($intention, $token);
	}

	/**
	 * @return \Devture\Bundle\SharedBundle\Token\TokenGeneratorInterface
	 */
	private function getCsrfTokenGenerator() {
		return $this->get('shared.csrf_token_generator');
	}

}
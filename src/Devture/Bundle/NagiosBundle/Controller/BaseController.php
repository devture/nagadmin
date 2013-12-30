<?php
namespace Devture\Bundle\NagiosBundle\Controller;

class BaseController extends \Devture\Bundle\FrameworkBundle\Controller\BaseController {

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

	/**
	 * @return \Devture\Bundle\NagiosBundle\NagiosCommand\Manager
	 */
	protected function getNagiosCommandManager() {
		return $this->getNs('nagios_command.manager');
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Log\Fetcher
	 */
	protected function getLogFetcher() {
		return $this->getNs('log.fetcher');
	}

	protected function tryDeployConfiguration() {
		$files = $this->getDeploymentConfigurationCollector()->collect();
		list($isValid, $_checkOutput) = $this->getDeploymentConfigurationTester()->test($files);
		if ($isValid) {
			$this->getDeploymentHandler()->deploy($files);
		}
	}

	protected function isValidCsrfToken($intention, $token) {
		return $this->getCsrfTokenManager()->isValid($intention, $token);
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Helper\AccessChecker
	 */
	protected function getAccessChecker() {
		return $this->getNs('helper.access_checker');
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Model\User|NULL
	 */
	protected function getUser() {
		return $this->get('user');
	}

	/**
	 * @return \Devture\Component\Form\Token\TokenManagerInterface
	 */
	private function getCsrfTokenManager() {
		return $this->get('devture_framework.csrf_token_manager');
	}

}
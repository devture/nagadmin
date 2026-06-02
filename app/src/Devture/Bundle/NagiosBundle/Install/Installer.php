<?php
namespace Devture\Bundle\NagiosBundle\Install;

class Installer {

	public function __construct(
		private \Pimple\Container $container,
		private string $notificationApiSecret,
	) {
	}

	/**
	 * @throws \RuntimeException
	 * @throws \Devture\Bundle\NagiosBundle\Exception\DeploymentFailedException
	 */
	public function install(): void {
		$this->updateResourceVariables();

		$this->deploy();
	}

	private function updateResourceVariables(): void {
		$resource = $this->getResourceRepository()->getResource();
		$resource->setVariable('$USER1$', '/opt/nagios/libexec');
		$resource->setVariable('$USER2$', $this->notificationApiSecret);
		$this->getResourceRepository()->update($resource);
	}

	/**
	 * @throws \RuntimeException
	 * @throws \Devture\Bundle\NagiosBundle\Exception\DeploymentFailedException
	 */
	private function deploy(): void {
		$files = $this->getDeploymentConfigurationCollector()->collect();

		list($isValid, $checkOutput) = $this->getDeploymentConfigurationTester()->test($files);

		if (!$isValid) {
			dd($checkOutput);
			throw new \RuntimeException(sprintf('Configuration failed validation: %s', $checkOutput));
		}

		// This may throw
		$this->getDeploymentHandler()->deploy($files, false);
	}

	private function getResourceRepository(): \Devture\Bundle\NagiosBundle\Repository\ResourceRepository {
		return $this->container['devture_nagios.resource.repository'];
	}

	private function getDeploymentConfigurationCollector(): \Devture\Bundle\NagiosBundle\Deployment\ConfigurationCollector {
		return $this->container['devture_nagios.deployment.configuration_collector'];
	}

	private function getDeploymentConfigurationTester(): \Devture\Bundle\NagiosBundle\Deployment\ConfigurationTester {
		return $this->container['devture_nagios.deployment.configuration_tester'];
	}

	private function getDeploymentHandler(): \Devture\Bundle\NagiosBundle\Deployment\Handler\DeploymentHandlerInterface {
		return $this->container['devture_nagios.deployment.handler'];
	}

}

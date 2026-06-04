<?php
namespace Devture\Bundle\NagiosBundle\Install;

use Devture\Bundle\NagiosBundle\Deployment\ConfigurationCollector;
use Devture\Bundle\NagiosBundle\Deployment\ConfigurationTester;
use Devture\Bundle\NagiosBundle\Deployment\Handler\DeploymentHandlerInterface;
use Devture\Bundle\NagiosBundle\Repository\ResourceRepository;

class Installer {

	public function __construct(
		private ResourceRepository $resourceRepository,
		private ConfigurationCollector $collector,
		private ConfigurationTester $tester,
		private DeploymentHandlerInterface $handler,
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
		$resource = $this->resourceRepository->getResource();
		$resource->setVariable('$USER1$', '/opt/nagios/libexec');
		$resource->setVariable('$USER2$', $this->notificationApiSecret);
		$this->resourceRepository->update($resource);
	}

	/**
	 * @throws \RuntimeException
	 * @throws \Devture\Bundle\NagiosBundle\Exception\DeploymentFailedException
	 */
	private function deploy(): void {
		$files = $this->collector->collect();

		list($isValid, $checkOutput) = $this->tester->test($files);

		if (!$isValid) {
			throw new \RuntimeException(sprintf('Configuration failed validation: %s', $checkOutput));
		}

		// This may throw
		$this->handler->deploy($files, false);
	}

}

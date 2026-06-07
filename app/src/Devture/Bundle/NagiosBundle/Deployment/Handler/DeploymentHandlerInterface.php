<?php
namespace Devture\Bundle\NagiosBundle\Deployment\Handler;

use Devture\Bundle\NagiosBundle\Exception\DeploymentFailedException;

interface DeploymentHandlerInterface {

	/**
	 * @param list<\Devture\Bundle\NagiosBundle\Deployment\ConfigurationFile> $configurationFiles
	 * @throws DeploymentFailedException
	 */
	public function deploy(array $configurationFiles, bool $reloadNagios): void;

}

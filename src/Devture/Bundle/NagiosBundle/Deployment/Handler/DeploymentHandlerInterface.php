<?php
namespace Devture\Bundle\NagiosBundle\Deployment\Handler;

use Devture\Bundle\NagiosBundle\Exception\DeploymentFailedException;

interface DeploymentHandlerInterface {

	/**
	 * @throws DeploymentFailedException
	 */
	public function deploy(array $configurationFiles, bool $reloadNagios): void;

}

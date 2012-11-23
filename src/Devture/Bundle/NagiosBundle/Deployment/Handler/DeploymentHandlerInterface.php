<?php
namespace Devture\Bundle\NagiosBundle\Deployment\Handler;

use Devture\Bundle\NagiosBundle\Exception\DeploymentFailedException;

interface DeploymentHandlerInterface {

	/**
	 * @param array $configurationFiles
	 * @throws DeploymentFailedException
	 */
	public function deploy(array $configurationFiles);

}
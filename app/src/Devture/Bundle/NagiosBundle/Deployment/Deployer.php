<?php
namespace Devture\Bundle\NagiosBundle\Deployment;

use Devture\Bundle\NagiosBundle\Deployment\Handler\DeploymentHandlerInterface;

/**
 * Best-effort redeploy of the current configuration, used after a model change:
 * collect the configuration, validate it, and (only if valid) write it out and
 * ask Nagios to reload. Mirrors the legacy BaseController::tryDeployConfiguration().
 */
class Deployer {

	public function __construct(
		private readonly ConfigurationCollector $collector,
		private readonly ConfigurationTester $tester,
		private readonly DeploymentHandlerInterface $handler,
	) {
	}

	public function tryDeploy(): bool {
		$files = $this->collector->collect();
		list($isValid, $_checkOutput) = $this->tester->test($files);
		if ($isValid) {
			$this->handler->deploy($files);
			return true;
		}
		return false;
	}

}

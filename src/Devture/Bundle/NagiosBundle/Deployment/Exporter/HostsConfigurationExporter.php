<?php
namespace Devture\Bundle\NagiosBundle\Deployment\Exporter;

use Devture\Bundle\NagiosBundle\Repository\HostRepository;
use Devture\Bundle\NagiosBundle\Deployment\ConfigurationFile;
use Devture\Bundle\NagiosBundle\Deployment\ObjectDefinition;
use Devture\Bundle\NagiosBundle\Model\Host;

class HostsConfigurationExporter implements ConfigurationExporterInterface {

	private $repository;

	public function __construct(HostRepository $repository) {
		$this->repository = $repository;
	}

	public function export() {
		$configurationFile = new ConfigurationFile('configuration/hosts.cfg', ConfigurationFile::TYPE_CONFIGURATION_FILE);

		/* @var $host Host */
		foreach ($this->repository->findAll() as $host) {
			$hostGroupNames = $host->getGroups();

			$definition = new ObjectDefinition('host');
			$definition->addDirective('use', 'nagadmin-host');
			$definition->addDirective('host_name', $host->getName());
			$definition->addDirective('alias', $host->getName());
			$definition->addDirective('address', $host->getAddress());
			if (count($hostGroupNames) > 0) {
				$definition->addDirective('hostgroups', implode(',', $hostGroupNames));
			}
			$configurationFile->addObjectDefinition($definition);
		}

		return $configurationFile;
	}

}

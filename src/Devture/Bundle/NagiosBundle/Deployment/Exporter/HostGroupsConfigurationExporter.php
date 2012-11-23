<?php
namespace Devture\Bundle\NagiosBundle\Deployment\Exporter;

use Devture\Bundle\NagiosBundle\Repository\HostRepository;
use Devture\Bundle\NagiosBundle\Deployment\ConfigurationFile;
use Devture\Bundle\NagiosBundle\Deployment\ObjectDefinition;
use Devture\Bundle\NagiosBundle\Model\Host;

class HostGroupsConfigurationExporter implements ConfigurationExporterInterface {

	private $repository;

	public function __construct(HostRepository $repository) {
		$this->repository = $repository;
	}

	public function export() {
		$configurationFile = new ConfigurationFile('configuration/hostgroups.cfg', ConfigurationFile::TYPE_CONFIGURATION_FILE);

		$groupsMap = array();
		/* @var $host Host */
		foreach ($this->repository->findAll() as $host) {
			$hostGroupNames = $host->getGroups();
			foreach ($host->getGroups() as $groupName) {
				$groupsMap[$groupName] = true;
			}
		}

		ksort($groupsMap);

		foreach (array_keys($groupsMap) as $groupName) {
			$definition = new ObjectDefinition('hostgroup');
			$definition->addDirective('hostgroup_name', $groupName);
			$definition->addDirective('alias', $groupName);
			$configurationFile->addObjectDefinition($definition);
		}

		return $configurationFile;
	}

}

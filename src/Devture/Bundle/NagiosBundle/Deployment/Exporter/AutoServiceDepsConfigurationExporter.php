<?php
namespace Devture\Bundle\NagiosBundle\Deployment\Exporter;

use Devture\Bundle\NagiosBundle\Deployment\ConfigurationFile;
use Devture\Bundle\NagiosBundle\Deployment\ObjectDefinition;
use Devture\Bundle\NagiosBundle\Repository\HostRepository;
use Devture\Bundle\NagiosBundle\Repository\ServiceRepository;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Model\Service;

class AutoServiceDepsConfigurationExporter implements ConfigurationExporterInterface {

	private $hostRepository;
	private $serviceRepository;
	private $masterServicesRegexes;

	public function __construct(HostRepository $hostRepository, ServiceRepository $serviceRepository, array $masterServicesRegexes) {
		$this->hostRepository = $hostRepository;
		$this->serviceRepository = $serviceRepository;
		$this->masterServicesRegexes = $masterServicesRegexes;
	}

	public function export() {
		$configurationFile = new ConfigurationFile('configuration/auto_sevice_deps.cfg', ConfigurationFile::TYPE_CONFIGURATION_FILE);

		/* @var $host Host */
		foreach ($this->hostRepository->findAll() as $host) {
			$services = $this->serviceRepository->findByHost($host);
			$masterServices = array();
			$dependentServices = array();

			/* @var $service Service */
			foreach ($services as $service) {
				if ($this->isMasterService($service)) {
					$masterServices[] = $service;
				} else {
					$dependentServices[] = $service;
				}
			}

			/* @var $masterService Service */
			/* @var $dependentService Service */
			foreach ($masterServices as $masterService) {
				foreach ($dependentServices as $dependentService) {
					$definition = new ObjectDefinition('servicedependency');
					$definition->addDirective('host_name', $host->getName());
					$definition->addDirective('service_description', $masterService->getName());
					$definition->addDirective('dependent_host_name', $host->getName());
					$definition->addDirective('dependent_service_description', $dependentService->getName());
					$definition->addDirective('notification_failure_criteria', 'w,c,u,p');
					$configurationFile->addObjectDefinition($definition);
				}
			}
		}

		return $configurationFile;
	}

	private function isMasterService(Service $service) {
		$name = $service->getName();
		foreach ($this->masterServicesRegexes as $regex) {
			if (preg_match($regex, $name)) {
				return true;
			}
		}
		return false;
	}

}

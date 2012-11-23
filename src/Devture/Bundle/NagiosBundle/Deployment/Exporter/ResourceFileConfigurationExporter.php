<?php
namespace Devture\Bundle\NagiosBundle\Deployment\Exporter;

use Devture\Bundle\NagiosBundle\Repository\ResourceRepository;
use Devture\Bundle\NagiosBundle\Deployment\ConfigurationFile;

class ResourceFileConfigurationExporter implements ConfigurationExporterInterface {

	private $repository;

	public function __construct(ResourceRepository $repository) {
		$this->repository = $repository;
	}

	public function export() {
		$configurationFile = new ConfigurationFile('resource.cfg', ConfigurationFile::TYPE_RESOURCE_FILE);

		$resource = $this->repository->getResource();

		foreach ($resource->getVariables() as $name => $value) {
			$configurationFile->addVariable($name, $value);
		}

		return $configurationFile;
	}

}

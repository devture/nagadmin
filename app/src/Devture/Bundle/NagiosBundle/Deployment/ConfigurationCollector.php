<?php
namespace Devture\Bundle\NagiosBundle\Deployment;

use Devture\Bundle\NagiosBundle\Deployment\Exporter\ConfigurationExporterInterface;

class ConfigurationCollector {

	/**
	 * @var list<ConfigurationExporterInterface>
	 */
	private array $exporters = array();

	public function addExporter(ConfigurationExporterInterface $exporter): void {
		$this->exporters[] = $exporter;
	}

	/**
	 * @return list<ConfigurationFile>
	 */
	public function collect() {
		$files = array();
		foreach ($this->exporters as $exporter) {
			$files[] = $exporter->export();
		}
		return $files;
	}

}

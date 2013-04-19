<?php
namespace Devture\Bundle\NagiosBundle\Deployment;

use Devture\Bundle\NagiosBundle\Deployment\Exporter\ConfigurationExporterInterface;

class ConfigurationCollector {

	private $exporters = array();

	public function addExporter(ConfigurationExporterInterface $exporter) {
		$this->exporters[] = $exporter;
	}

	/**
	 * @return ConfigurationFile[]
	 */
	public function collect() {
		$files = array();
		foreach ($this->exporters as $exporter) {
			$files[] = $exporter->export();
		}
		return $files;
	}

}

<?php
namespace Devture\Bundle\NagiosBundle\Deployment\Exporter;

use Devture\Bundle\NagiosBundle\Deployment\ConfigurationFile;

interface ConfigurationExporterInterface {

	/**
	 * @return ConfigurationFile
	 */
	public function export();

}

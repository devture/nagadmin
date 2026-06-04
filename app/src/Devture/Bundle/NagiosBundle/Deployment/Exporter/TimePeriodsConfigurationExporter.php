<?php
namespace Devture\Bundle\NagiosBundle\Deployment\Exporter;

use Devture\Bundle\NagiosBundle\Repository\TimePeriodRepository;
use Devture\Bundle\NagiosBundle\Deployment\ConfigurationFile;
use Devture\Bundle\NagiosBundle\Deployment\ObjectDefinition;
use Devture\Bundle\NagiosBundle\Model\TimePeriod;

class TimePeriodsConfigurationExporter implements ConfigurationExporterInterface {

	private $repository;

	public function __construct(TimePeriodRepository $repository) {
		$this->repository = $repository;
	}

	public function export() {
		$configurationFile = new ConfigurationFile('configuration/timeperiods.cfg', ConfigurationFile::TYPE_CONFIGURATION_FILE);

		/* @var $timePeriod TimePeriod */
		foreach ($this->repository->findAll() as $timePeriod) {
			$definition = new ObjectDefinition('timeperiod');
			$definition->addDirective('timeperiod_name', $timePeriod->getName());
			$definition->addDirective('alias', $timePeriod->getName());
			foreach ($timePeriod->getRules() as $rule) {
				$definition->addDirective($rule->getDateRange(), $rule->getTimeRange());
			}
			$configurationFile->addObjectDefinition($definition);
		}

		return $configurationFile;
	}

}

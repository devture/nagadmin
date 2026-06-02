<?php
namespace Devture\Bundle\NagiosBundle\Deployment\Exporter;

use Devture\Bundle\NagiosBundle\Repository\CommandRepository;
use Devture\Bundle\NagiosBundle\Deployment\ConfigurationFile;
use Devture\Bundle\NagiosBundle\Deployment\ObjectDefinition;
use Devture\Bundle\NagiosBundle\Model\Command;

class CommandsConfigurationExporter implements ConfigurationExporterInterface {

	private $repository;

	public function __construct(CommandRepository $repository) {
		$this->repository = $repository;
	}

	public function export() {
		$configurationFile = new ConfigurationFile('configuration/commands.cfg', ConfigurationFile::TYPE_CONFIGURATION_FILE);

		/* @var $command Command */
		foreach ($this->repository->findAll() as $command) {
			$definition = new ObjectDefinition('command');
			$definition->addDirective('command_name', $command->getName());
			$definition->addDirective('command_line', $command->getLine());
			$configurationFile->addObjectDefinition($definition);
		}

		return $configurationFile;
	}

}

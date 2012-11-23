<?php
namespace Devture\Bundle\NagiosBundle\Deployment\Exporter;

use Devture\Bundle\NagiosBundle\Repository\ServiceRepository;
use Devture\Bundle\NagiosBundle\Deployment\ConfigurationFile;
use Devture\Bundle\NagiosBundle\Deployment\ObjectDefinition;
use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Model\ServiceCommandArgument;
use Devture\Bundle\NagiosBundle\Model\Contact;

class ServicesConfigurationExporter implements ConfigurationExporterInterface {

	private $repository;

	public function __construct(ServiceRepository $repository) {
		$this->repository = $repository;
	}

	public function export() {
		$configurationFile = new ConfigurationFile('configuration/services.cfg', ConfigurationFile::TYPE_CONFIGURATION_FILE);

		/* @var $service Service */
		foreach ($this->repository->findAll() as $service) {
			$contactNames = array_map(function (Contact $contact) {
				return $contact->getName();
			}, $service->getContacts());

			$commandName = $service->getCommand()->getName();
			$commandArgs = array_map(function (ServiceCommandArgument $argument) {
				return $argument->getValue();
			}, $service->getArguments());
			$checkCommand = $commandName . (count($commandArgs) === 0 ? '' : '!' . implode('!', $commandArgs));

			$definition = new ObjectDefinition('service');
			$definition->addDirective('use', 'nagadmin-service');
			$definition->addDirective('host_name', $service->getHost()->getName());
			$definition->addDirective('service_description', $service->getName());
			$definition->addDirective('check_command', $checkCommand);
			$definition->addDirective('max_check_attempts', $service->getMaxCheckAttempts());
			$definition->addDirective('check_interval', $service->getCheckInterval());
			$definition->addDirective('retry_interval', $service->getRetryInterval());
			$definition->addDirective('notification_interval', $service->getNotificationInterval());
			if (count($contactNames) > 0) {
				$definition->addDirective('contacts', implode(",", $contactNames));
			}
			$configurationFile->addObjectDefinition($definition);
		}

		return $configurationFile;
	}

}

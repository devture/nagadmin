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
			if (!$service->isEnabled()) {
				continue;
			}

			$contactNames = array_map(function (Contact $contact) {
				return $contact->getName();
			}, $service->getContacts());

			$commandName = $service->getCommand()->getName();
			$commandArgs = array_map(function (ServiceCommandArgument $argument) use ($service) {
				return $this->escapeShellArg($service, $argument->getValue());
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

	private function escapeShellArg(Service $service, $argumentValue) {
		//Macros won't work in command arguments (because we escape $ bellow).
		//Let's handle this single macro here (at generation time), for convenience.
		if ($argumentValue === '$HOSTADDRESS$') {
			return escapeshellarg($service->getHost()->getAddress());
		}

		//This argument should be turned into safe text.
		//`escapeshellarg($argumentValue)` will not work for our special needs.
		//The resulting string will be similar (single-quote surrounded), but not quite the same.

		$argumentValue = str_replace('\\', '\\\\', $argumentValue);

		$argumentValue = str_replace('$', '$$', $argumentValue);

		//`!` is used to separate the arguments passed to the command. It needs to be escaped.
		$argumentValue = str_replace('!', '\\!', $argumentValue);

		$argumentValue = str_replace("\0", '', $argumentValue);

		//Escape single-quotes within the would-be-single-quote-surrounded-string.
		//Basically turning `'can't'` into `'can'"'"'t' (break out of single quotes, concat a single quote, re-open single quotes)
		//This is similar to `escapeshellarg($string)`, but escpeshellarg turns `'can't'` into `'can'\''t'`,
		//and Nagios does not seem to like that way of escaping single quotes.
		$argumentValue = str_replace(
			"'",
			(
				"'" . //break out of single quotes
				'"' . //concat a new double-quoted-string
				"'" . //add the quote we want to escape/preserve
				'"' . //close the new double-quoted-string
				"'" //reopen the single quotes we originally broke out of
			),
			$argumentValue
		);
		return "'" . $argumentValue . "'";
	}

}

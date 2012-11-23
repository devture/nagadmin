<?php
namespace Devture\Bundle\NagiosBundle\Deployment\Exporter;

use Devture\Bundle\NagiosBundle\Repository\ContactRepository;
use Devture\Bundle\NagiosBundle\Deployment\ConfigurationFile;
use Devture\Bundle\NagiosBundle\Deployment\ObjectDefinition;
use Devture\Bundle\NagiosBundle\Model\Contact;

class ContactsConfigurationExporter implements ConfigurationExporterInterface {

	private $repository;

	public function __construct(ContactRepository $repository) {
		$this->repository = $repository;
	}

	public function export() {
		$configurationFile = new ConfigurationFile('configuration/contacts.cfg', ConfigurationFile::TYPE_CONFIGURATION_FILE);

		/* @var $contact Contact */
		foreach ($this->repository->findAll() as $contact) {
			$definition = new ObjectDefinition('contact');
			$definition->addDirective('use', 'nagadmin-contact');
			$definition->addDirective('contact_name', $contact->getName());
			$definition->addDirective('email', $contact->getEmail());
			$definition->addDirective('host_notification_period', $contact->getTimePeriod()->getName());
			$definition->addDirective('service_notification_period', $contact->getTimePeriod()->getName());
			$definition->addDirective('service_notification_commands', $contact->getServiceNotificationCommand()->getName());
			foreach ($contact->getAddresses() as $slot => $address) {
				$definition->addDirective('address' . $slot, $address);
			}
			$configurationFile->addObjectDefinition($definition);
		}

		return $configurationFile;
	}

}

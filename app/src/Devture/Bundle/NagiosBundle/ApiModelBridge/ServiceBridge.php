<?php
namespace Devture\Bundle\NagiosBundle\ApiModelBridge;

use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Model\Contact;

class ServiceBridge {

	private $hostBridge;
	private $contactBridge;

	public function __construct(HostBridge $hostBridge, ContactBridge $contactBridge) {
		$this->hostBridge = $hostBridge;
		$this->contactBridge = $contactBridge;
	}

	public function export(Service $entity) {
		$contacts = array_map(function (Contact $contact) {
			return $this->contactBridge->export($contact);
		}, $entity->getContacts());

		return array(
			'id' => (string) $entity->getId(),
			'enabled' => $entity->isEnabled(),
			'name' => $entity->getName(),
			'host' => $this->hostBridge->export($entity->getHost()),
			'contacts' => $contacts,
		);
	}

}
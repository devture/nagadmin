<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Bundle\SharedBundle\Model\BaseModel;

class Service extends BaseModel {

	/**
	 * @var Host
	 */
	private $host;

	/**
	 * @var Command
	 */
	private $command;

	private $contacts = array();

	public function setHost(Host $host) {
		$this->host = $host;
	}

	public function getHost() {
		return $this->host;
	}

	public function setCommand(Command $command) {
		$this->command = $command;
	}

	public function getCommand() {
		return $this->command;
	}

	public function setName($value) {
		$this->setAttribute('name', $value);
	}

	public function getName() {
		return $this->getAttribute('name');
	}

	public function setEnabled($value) {
		$this->setAttribute('enabled', (bool) $value);
	}

	public function isEnabled() {
		return $this->getAttribute('enabled', true);
	}

	public function setMaxCheckAttempts($value) {
		$this->setAttribute('maxCheckAttempts', is_numeric($value) ? (int)$value : $value);
	}

	public function getMaxCheckAttempts() {
		return $this->getAttribute('maxCheckAttempts');
	}

	public function setCheckInterval($value) {
		$this->setAttribute('checkInterval', is_numeric($value) ? (int)$value : $value);
	}

	public function getCheckInterval() {
		return $this->getAttribute('checkInterval');
	}

	public function setRetryInterval($value) {
		$this->setAttribute('retryInterval', is_numeric($value) ? (int)$value : $value);
	}

	public function getRetryInterval() {
		return $this->getAttribute('retryInterval');
	}

	public function setNotificationInterval($value) {
		$this->setAttribute('notificationInterval', is_numeric($value) ? (int)$value : $value);
	}

	public function getNotificationInterval() {
		return $this->getAttribute('notificationInterval');
	}

	public function setNotificationPeriod($value) {
		$this->setAttribute('notificationPeriod', is_numeric($value) ? (int)$value : $value);
	}

	public function getNotificationPeriod() {
		return $this->getAttribute('notificationPeriod');
	}

	public function clearArguments() {
		$this->setAttribute('arguments', array());
	}

	public function addArgument(ServiceCommandArgument $argument) {
		$arguments = $this->getArgumentsRaw();
		$arguments[] = $argument->export();
		$this->setAttribute('arguments', $arguments);
	}

	private function getArgumentsRaw() {
		return $this->getAttribute('arguments', array());
	}

	public function getArguments() {
		$arguments = array();
		foreach ($this->getArgumentsRaw() as $argumentData) {
			$arguments[] = new ServiceCommandArgument($argumentData);
		}
		return $arguments;
	}

	public function getArgumentById($id) {
		foreach ($this->getArguments() as $argument) {
			if ($argument->getId() === $id) {
				return $argument;
			}
		}
		return null;
	}

	public function clearContacts() {
		$this->contacts = array();
	}

	public function addContact(Contact $contact) {
		if (!in_array($contact, $this->contacts)) {
			$this->contacts[] = $contact;
		}
	}

	public function removeContact(Contact $contact) {
		$contacts = array();
		foreach ($this->contacts as $currentContact) {
			if ($contact !== $currentContact) {
				$contacts[] = $currentContact;
			}
		}
		$this->contacts = $contacts;
	}

	public function getContacts() {
		return $this->contacts;
	}

}
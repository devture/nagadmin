<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Component\DBAL\Model\BaseModel;

class Service extends BaseModel {

	/**
	 * @var Host
	 */
	private $host;

	/**
	 * @var Command
	 */
	private $command;

	/**
	 * @var list<Contact>
	 */
	private array $contacts = array();

	public function setHost(Host $host): void {
		$this->host = $host;
	}

	public function getHost(): Host {
		return $this->host;
	}

	public function setCommand(Command $command): void {
		$this->command = $command;
	}

	public function getCommand(): Command {
		return $this->command;
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function setName($value) {
		$this->setAttribute('name', $value);
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->getAttribute('name');
	}

	/**
	 * @param mixed $value
	 * @return void
	 */
	public function setEnabled($value) {
		$this->setAttribute('enabled', (bool) $value);
	}

	/**
	 * @return bool
	 */
	public function isEnabled() {
		return $this->getAttribute('enabled', true);
	}

	/**
	 * @param mixed $value
	 * @return void
	 */
	public function setMaxCheckAttempts($value) {
		$this->setAttribute('maxCheckAttempts', is_numeric($value) ? (int) $value : $value);
	}

	/**
	 * @return int
	 */
	public function getMaxCheckAttempts() {
		return $this->getAttribute('maxCheckAttempts');
	}

	/**
	 * @param mixed $value
	 * @return void
	 */
	public function setCheckInterval($value) {
		$this->setAttribute('checkInterval', is_numeric($value) ? (int) $value : $value);
	}

	/**
	 * @return int
	 */
	public function getCheckInterval() {
		return $this->getAttribute('checkInterval');
	}

	/**
	 * @param mixed $value
	 * @return void
	 */
	public function setRetryInterval($value) {
		$this->setAttribute('retryInterval', is_numeric($value) ? (int) $value : $value);
	}

	/**
	 * @return int
	 */
	public function getRetryInterval() {
		return $this->getAttribute('retryInterval');
	}

	/**
	 * @param mixed $value
	 * @return void
	 */
	public function setNotificationInterval($value) {
		$this->setAttribute('notificationInterval', is_numeric($value) ? (int) $value : $value);
	}

	/**
	 * @return int
	 */
	public function getNotificationInterval() {
		return $this->getAttribute('notificationInterval');
	}

	/**
	 * @param mixed $value
	 * @return void
	 */
	public function setNotificationPeriod($value) {
		$this->setAttribute('notificationPeriod', is_numeric($value) ? (int) $value : $value);
	}

	/**
	 * @return string
	 */
	public function getNotificationPeriod() {
		return $this->getAttribute('notificationPeriod');
	}

	public function clearArguments(): void {
		$this->setAttribute('arguments', array());
	}

	public function addArgument(ServiceCommandArgument $argument): void {
		$arguments = $this->getArgumentsRaw();
		$arguments[] = $argument->export();
		$this->setAttribute('arguments', $arguments);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function getArgumentsRaw() {
		return $this->getAttribute('arguments', array());
	}

	/**
	 * @return list<ServiceCommandArgument>
	 */
	public function getArguments() {
		$arguments = array();
		foreach ($this->getArgumentsRaw() as $argumentData) {
			$arguments[] = new ServiceCommandArgument($argumentData);
		}
		return $arguments;
	}

	/**
	 * @param mixed $id
	 */
	public function getArgumentById($id): ?ServiceCommandArgument {
		foreach ($this->getArguments() as $argument) {
			if ($argument->getId() === $id) {
				return $argument;
			}
		}
		return null;
	}

	public function clearContacts(): void {
		$this->contacts = array();
	}

	public function addContact(Contact $contact): void {
		if (!in_array($contact, $this->contacts)) {
			$this->contacts[] = $contact;
		}
	}

	public function removeContact(Contact $contact): void {
		$contacts = array();
		foreach ($this->contacts as $currentContact) {
			if ($contact !== $currentContact) {
				$contacts[] = $currentContact;
			}
		}
		$this->contacts = $contacts;
	}

	/**
	 * @return list<Contact>
	 */
	public function getContacts() {
		$contacts = $this->contacts;
		usort($contacts, function (Contact $a, Contact $b) {
			return strcmp($a->getName(), $b->getName());
		});
		return $contacts;
	}

}

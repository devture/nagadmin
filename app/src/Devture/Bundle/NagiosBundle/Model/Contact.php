<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Component\DBAL\Model\BaseModel;
use Devture\Bundle\NagiosBundle\Model\User;

class Contact extends BaseModel {

	const ADDRESS_SLOTS_COUNT = 6;

	/**
	 * @var TimePeriod
	 */
	private $timePeriod;

	/**
	 * @var Command
	 */
	private $serviceNotificationCommand;

	/**
	 * @var User|null
	 */
	private $user;

	public function setTimePeriod(TimePeriod $timePeriod): void {
		$this->timePeriod = $timePeriod;
	}

	public function getTimePeriod(): TimePeriod {
		return $this->timePeriod;
	}

	public function setServiceNotificationCommand(Command $command): void {
		$this->serviceNotificationCommand = $command;
	}

	public function getServiceNotificationCommand(): Command {
		return $this->serviceNotificationCommand;
	}

	public function setUser(?User $user): void {
		$this->user = $user;
	}

	/**
	 * @return User|NULL
	 */
	public function getUser() {
		return $this->user;
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
	 * @param string $value
	 * @return void
	 */
	public function setEmail($value) {
		$this->setAttribute('email', $value);
	}

	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->getAttribute('email');
	}

	public function clearAddresses(): void {
		$this->setAttribute('addresses', array());
	}

	/**
	 * @param int $slot
	 * @param string $address
	 * @return void
	 */
	public function addAddress($slot, $address) {
		$addresses = $this->getAddresses();
		$addresses[(string)$slot] = $address;
		$this->setAttribute('addresses', $addresses);
	}

	/**
	 * @return array<string, string>
	 */
	public function getAddresses() {
		return $this->getAttribute('addresses', array());
	}

	/**
	 * @param int $slot
	 * @return string|null
	 */
	public function getAddressBySlot($slot) {
		$addresses = $this->getAddresses();
		return isset($addresses[(string)$slot]) ? $addresses[(string)$slot] : null;
	}

}

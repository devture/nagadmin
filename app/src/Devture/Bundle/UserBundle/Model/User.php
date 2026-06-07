<?php
namespace Devture\Bundle\UserBundle\Model;

use Devture\Component\DBAL\Model\BaseModel;

class User extends BaseModel {

	const ROLE_MASTER = 'all';

	/**
	 * @param string $value
	 * @return void
	 */
	public function setUsername($value) {
		$this->setAttribute('username', $value);
	}

	/**
	 * @return string
	 */
	public function getUsername() {
		return $this->getAttribute('username', '');
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function setEmail($value) {
		$this->setAttribute('email', $value);
	}

	/**
	 * @return string|null
	 */
	public function getEmail() {
		return $this->getAttribute('email', null);
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function setPassword($value) {
		$this->setAttribute('password', $value);
	}

	/**
	 * @return string
	 */
	public function getPassword() {
		return $this->getAttribute('password', '');
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function setName($value) {
		$this->setAttribute('name', trim($value));
	}

	/**
	 * @return string|null
	 */
	public function getName() {
		return $this->getAttribute('name', null);
	}

	/**
	 * @param list<string> $roles
	 * @return void
	 */
	public function setRoles(array $roles) {
		if (in_array(self::ROLE_MASTER, $roles)) {
			//No need for specifying other roles if the master role was specified
			$roles = array(self::ROLE_MASTER);
		}
		$this->setAttribute('roles', $roles);
	}

	/**
	 * @return list<string>
	 */
	public function getRoles() {
		return $this->getAttribute('roles', array());
	}

	/**
	 * @param string $role
	 */
	public function hasRole($role): bool {
		return in_array($role, $this->getRoles());
	}

}

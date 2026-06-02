<?php
namespace Devture\Bundle\UserBundle\Model;

use Devture\Component\DBAL\Model\BaseModel;

class User extends BaseModel {

	const ROLE_MASTER = 'all';

	public function setUsername($value) {
		$this->setAttribute('username', $value);
	}

	public function getUsername() {
		return $this->getAttribute('username', '');
	}

	public function setEmail($value) {
		$this->setAttribute('email', $value);
	}

	public function getEmail() {
		return $this->getAttribute('email', null);
	}

	public function setPassword($value) {
		$this->setAttribute('password', $value);
	}

	public function getPassword() {
		return $this->getAttribute('password', '');
	}

	public function setName($value) {
		$this->setAttribute('name', trim($value));
	}

	public function getName() {
		return $this->getAttribute('name', null);
	}

	public function setRoles(array $roles) {
		if (in_array(self::ROLE_MASTER, $roles)) {
			//No need for specifying other roles if the master role was specified
			$roles = array(self::ROLE_MASTER);
		}
		$this->setAttribute('roles', $roles);
	}

	public function getRoles() {
		return $this->getAttribute('roles', array());
	}

	public function hasRole($role) {
		return in_array($role, $this->getRoles());
	}

}

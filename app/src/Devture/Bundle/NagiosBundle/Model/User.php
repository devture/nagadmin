<?php
namespace Devture\Bundle\NagiosBundle\Model;

class User extends \Devture\Bundle\UserBundle\Model\User {

	public function clearGroups() {
		$this->setAttribute('groups', array());
	}

	public function addGroup($name) {
		$groups = $this->getGroups();
		if (!in_array($name, $groups)) {
			$groups[] = $name;
			sort($groups);
			$this->setAttribute('groups', $groups);
		}
	}

	public function getGroups() {
		return $this->getAttribute('groups', array());
	}

	public function hasGroup($name) {
		return (in_array($name, $this->getGroups()));
	}

}
<?php
namespace Devture\Bundle\NagiosBundle\Model;

class User extends \Devture\Bundle\UserBundle\Model\User {

	public function clearGroups(): void {
		$this->setAttribute('groups', array());
	}

	/**
	 * @param string $name
	 * @return void
	 */
	public function addGroup($name) {
		$groups = $this->getGroups();
		if (!in_array($name, $groups)) {
			$groups[] = $name;
			sort($groups);
			$this->setAttribute('groups', $groups);
		}
	}

	/**
	 * @return list<string>
	 */
	public function getGroups() {
		return $this->getAttribute('groups', array());
	}

	/**
	 * @param string $name
	 */
	public function hasGroup($name): bool {
		return (in_array($name, $this->getGroups()));
	}

}

<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Bundle\SharedBundle\Model\BaseModel;

class Host extends BaseModel {

	public function setName($value) {
		$this->setAttribute('name', $value);
	}

	public function getName() {
		return $this->getAttribute('name');
	}

	public function setAddress($value) {
		$this->setAttribute('address', $value);
	}

	public function getAddress() {
		return $this->getAttribute('address');
	}

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

}
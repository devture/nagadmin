<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Component\DBAL\Model\BaseModel;

class Host extends BaseModel {

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
	public function setAddress($value) {
		$this->setAttribute('address', $value);
	}

	/**
	 * @return string
	 */
	public function getAddress() {
		return $this->getAttribute('address');
	}

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

}

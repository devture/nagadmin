<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Component\DBAL\Model\BaseModel;

class CommandArgument extends BaseModel {

	/**
	 * @param string $value
	 * @return void
	 */
	public function setDescription($value) {
		$this->setAttribute('description', $value);
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->getAttribute('description');
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function setDefaultValue($value) {
		$this->setAttribute('defaultValue', $value);
	}

	/**
	 * @return string
	 */
	public function getDefaultValue() {
		return $this->getAttribute('defaultValue');
	}

}

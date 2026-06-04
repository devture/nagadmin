<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Component\DBAL\Model\BaseModel;

class CommandArgument extends BaseModel {

	public function setDescription($value) {
		$this->setAttribute('description', $value);
	}

	public function getDescription() {
		return $this->getAttribute('description');
	}

	public function setDefaultValue($value) {
		$this->setAttribute('defaultValue', $value);
	}

	public function getDefaultValue() {
		return $this->getAttribute('defaultValue');
	}

}
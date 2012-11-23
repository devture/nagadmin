<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Bundle\SharedBundle\Model\BaseModel;

class ServiceCommandArgument extends BaseModel {

	public function setValue($value) {
		$this->setAttribute('value', $value);
	}

	public function getValue() {
		return $this->getAttribute('value');
	}

}
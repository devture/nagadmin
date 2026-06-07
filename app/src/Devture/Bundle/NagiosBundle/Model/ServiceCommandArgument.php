<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Component\DBAL\Model\BaseModel;

class ServiceCommandArgument extends BaseModel {

	/**
	 * @param string $value
	 * @return void
	 */
	public function setValue($value) {
		$this->setAttribute('value', $value);
	}

	/**
	 * @return string
	 */
	public function getValue() {
		return $this->getAttribute('value');
	}

}

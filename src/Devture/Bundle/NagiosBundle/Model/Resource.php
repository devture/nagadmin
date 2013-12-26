<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Component\DBAL\Model\BaseModel;

class Resource extends BaseModel {

	const USER_VARIABLES_COUNT = 32;

	public function clearVariables() {
		$this->setAttribute('variables', array());
	}

	public function setVariable($name, $value) {
		$variables = $this->getVariables();
		$variables[$name] = $value;
		$this->setAttribute('variables', $variables);
	}

	public function getVariables() {
		return $this->getAttribute('variables', array());
	}

	public function getVariableByName($name) {
		$variables = $this->getVariables();
		return isset($variables[$name]) ? $variables[$name] : null;
	}

}
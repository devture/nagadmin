<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Component\DBAL\Model\BaseModel;

class Resource extends BaseModel {

	const USER_VARIABLES_COUNT = 32;

	public function clearVariables(): void {
		$this->setAttribute('variables', array());
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public function setVariable($name, $value) {
		$variables = $this->getVariables();
		$variables[$name] = $value;
		$this->setAttribute('variables', $variables);
	}

	/**
	 * @return array<string, string>
	 */
	public function getVariables() {
		return $this->getAttribute('variables', array());
	}

	/**
	 * @param string $name
	 * @return string|null
	 */
	public function getVariableByName($name) {
		$variables = $this->getVariables();
		return isset($variables[$name]) ? $variables[$name] : null;
	}

}

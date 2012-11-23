<?php
namespace Devture\Bundle\NagiosBundle\Deployment;

class ObjectDefinition {

	private $type;
	private $directives = array();

	public function __construct($type) {
		$this->type = $type;
	}

	public function addDirective($name, $value) {
		$this->directives[] = array('name' => $name, 'value' => $value);
	}

	public function getConfiguration() {
		ob_start();
		echo "define ", $this->type, " {\n";

		foreach ($this->directives as $directive) {
			echo "\t", $directive['name'], "\t", $directive['value'], "\n";
		}

		echo "}";
		return ob_get_clean();
	}

}
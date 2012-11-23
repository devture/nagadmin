<?php
namespace Devture\Bundle\NagiosBundle\Deployment;

class ConfigurationFile {

	const TYPE_CONFIGURATION_FILE = 0;
	const TYPE_RESOURCE_FILE = 1;

	private $path;
	private $type;
	private $definitions = array();
	private $variables = array();

	public function __construct($path, $type) {
		$this->path = $path;
		$this->type = $type;
	}

	public function getPath() {
		return $this->path;
	}

	public function getType() {
		return $this->type;
	}

	public function addObjectDefinition(ObjectDefinition $definition) {
		$this->definitions[] = $definition;
	}

	public function addVariable($name, $value) {
		$this->variables[] = array('name' => $name, 'value' => $value);
	}

	public function getConfiguration() {
		ob_start();

		foreach ($this->definitions as $definition) {
			echo $definition->getConfiguration(), "\n\n";
		}

		foreach ($this->variables as $variableData) {
			echo $variableData['name'], "=", $variableData['value'], "\n\n";
		}

		return ob_get_clean();
	}

}
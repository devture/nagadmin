<?php
namespace Devture\Bundle\NagiosBundle\Deployment;

class ConfigurationFile {

	const TYPE_CONFIGURATION_FILE = 0;
	const TYPE_RESOURCE_FILE = 1;

	private string $path;
	private int $type;

	/**
	 * @var list<ObjectDefinition>
	 */
	private array $definitions = array();

	/**
	 * @var list<array{name: string, value: string}>
	 */
	private array $variables = array();

	public function __construct(string $path, int $type) {
		$this->path = $path;
		$this->type = $type;
	}

	public function getPath(): string {
		return $this->path;
	}

	public function getType(): int {
		return $this->type;
	}

	public function addObjectDefinition(ObjectDefinition $definition): void {
		$this->definitions[] = $definition;
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public function addVariable($name, $value) {
		$this->variables[] = array('name' => $name, 'value' => $value);
	}

	public function getConfiguration(): string {
		ob_start();

		foreach ($this->definitions as $definition) {
			echo $definition->getConfiguration(), "\n\n";
		}

		foreach ($this->variables as $variableData) {
			echo $variableData['name'], "=", $variableData['value'], "\n\n";
		}

		return (string) ob_get_clean();
	}

}

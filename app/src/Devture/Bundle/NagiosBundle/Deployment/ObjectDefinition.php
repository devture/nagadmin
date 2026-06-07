<?php
namespace Devture\Bundle\NagiosBundle\Deployment;

class ObjectDefinition {

	private string $type;

	/**
	 * @var list<array{name: string, value: string|int}>
	 */
	private array $directives = array();

	public function __construct(string $type) {
		$this->type = $type;
	}

	/**
	 * @param string $name
	 * @param string|int $value
	 * @return void
	 */
	public function addDirective($name, $value) {
		$this->directives[] = array('name' => $name, 'value' => $value);
	}

	public function getConfiguration(): string {
		ob_start();
		echo "define ", $this->type, " {\n";

		foreach ($this->directives as $directive) {
			echo "\t", $directive['name'], "\t", $directive['value'], "\n";
		}

		echo "}";
		return (string) ob_get_clean();
	}

}

<?php
namespace Devture\Bundle\NagiosBundle\Status;

abstract class Status {

	const TYPE_INFO = 'info';
	const TYPE_PROGRAM_STATUS = 'programstatus';
	const TYPE_SERVICE_STATUS = 'servicestatus';

	private string $type;

	/**
	 * @var array<string, string>
	 */
	private array $directives;

	/**
	 * @param array<string, string> $directives
	 */
	public function __construct(string $type, array $directives) {
		$this->type = $type;
		$this->directives = $directives;
	}

	public function getType(): string {
		return $this->type;
	}

	/**
	 * @param string $name
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	public function getDirective($name, $defaultValue = null) {
		return (isset($this->directives[$name]) ? $this->directives[$name] : $defaultValue);
	}

	/**
	 * @return array<string, string>
	 */
	public function getDirectives(): array {
		return $this->directives;
	}

}

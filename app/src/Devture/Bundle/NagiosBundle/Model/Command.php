<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Component\DBAL\Model\BaseModel;

class Command extends BaseModel {

	const TYPE_SERVICE_CHECK = 'serviceCheck';

	const TYPE_SERVICE_NOTIFICATION = 'serviceNotification';

	/**
	 * @param string $value
	 * @return void
	 */
	public function setName($value) {
		$this->setAttribute('name', $value);
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->getAttribute('name');
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function setTitle($value) {
		$this->setAttribute('title', $value);
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->getAttribute('title');
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function setType($value) {
		$this->setAttribute('type', $value);
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->getAttribute('type');
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function setLine($value) {
		$this->setAttribute('line', $value);
	}

	/**
	 * @return string
	 */
	public function getLine() {
		return $this->getAttribute('line');
	}

	public function getLineArgumentsCount(): int {
		if (!preg_match_all('/\$ARG([0-9]+)\$/', $this->getLine(), $matches)) {
			return 0;
		}
		list($_fullMatches, $argumentNumbersMatches) = $matches;
		$numbers = array_map('intval', $argumentNumbersMatches);
		return $numbers === array() ? 0 : max($numbers);
	}

	public function clearArguments(): void {
		$this->setAttribute('arguments', array());
	}

	public function addArgument(CommandArgument $argument): void {
		$arguments = $this->getArgumentsRaw();
		$arguments[] = $argument->export();
		$this->setAttribute('arguments', $arguments);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function getArgumentsRaw() {
		return $this->getAttribute('arguments', array());
	}

	/**
	 * @return list<CommandArgument>
	 */
	public function getArguments() {
		$arguments = array();
		foreach ($this->getArgumentsRaw() as $argumentData) {
			$arguments[] = new CommandArgument($argumentData);
		}
		return $arguments;
	}

	/**
	 * @return list<string>
	 */
	public static function getTypes() {
		$r = new \ReflectionClass(__CLASS__);
		$types = array();
		foreach ($r->getConstants() as $constantName => $constantValue) {
			if (strpos($constantName, 'TYPE_') === 0) {
				$types[] = $constantValue;
			}
		}
		return $types;
	}

}

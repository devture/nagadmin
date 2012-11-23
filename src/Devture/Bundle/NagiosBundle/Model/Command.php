<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Bundle\SharedBundle\Model\BaseModel;

class Command extends BaseModel {

	const TYPE_SERVICE_CHECK = 'serviceCheck';

	const TYPE_SERVICE_NOTIFICATION = 'serviceNotification';

	public function setName($value) {
		$this->setAttribute('name', $value);
	}

	public function getName() {
		return $this->getAttribute('name');
	}

	public function setTitle($value) {
		$this->setAttribute('title', $value);
	}

	public function getTitle() {
		return $this->getAttribute('title');
	}

	public function setType($value) {
		$this->setAttribute('type', $value);
	}

	public function getType() {
		return $this->getAttribute('type');
	}

	public function setLine($value) {
		$this->setAttribute('line', $value);
	}

	public function getLine() {
		return $this->getAttribute('line');
	}

	public function getLineArgumentsCount() {
		if (!preg_match_all('/\$ARG([0-9]+)\$/', $this->getLine(), $matches)) {
			return 0;
		}
		list($_fullMatches, $argumentNumbersMatches) = $matches;
		return max(array_map('intval', $argumentNumbersMatches));
	}

	public function clearArguments() {
		$this->setAttribute('arguments', array());
	}

	public function addArgument(CommandArgument $argument) {
		$arguments = $this->getArgumentsRaw();
		$arguments[] = $argument->export();
		$this->setAttribute('arguments', $arguments);
	}

	private function getArgumentsRaw() {
		return $this->getAttribute('arguments', array());
	}

	public function getArguments() {
		$arguments = array();
		foreach ($this->getArgumentsRaw() as $argumentData) {
			$arguments[] = new CommandArgument($argumentData);
		}
		return $arguments;
	}

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
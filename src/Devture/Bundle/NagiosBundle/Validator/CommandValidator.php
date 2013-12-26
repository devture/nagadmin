<?php
namespace Devture\Bundle\NagiosBundle\Validator;

use Devture\Bundle\SharedBundle\Validator\BaseValidator;
use Devture\Bundle\SharedBundle\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Repository\CommandRepository;
use Devture\Bundle\NagiosBundle\Model\Command;

class CommandValidator extends BaseValidator {

	private $repository;

	public function __construct(CommandRepository $repository) {
		$this->repository = $repository;
	}

	/**
	 * @param Command $entity
	 * @param array $options
	 * @return \Devture\Bundle\SharedBundle\Validator\ViolationsList
	 */
	public function validate($entity, array $options = array()) {
		$violations = parent::validate($entity, $options);

		$name = $entity->getName();
		if (strlen($name) < 3 || !preg_match("/^[a-z][a-z0-9_\-\.]+$/", $name)) {
			$violations->add('name', 'Invalid name.');
		} else {
			try {
				$ent = $this->repository->findOneBy(array('name' => $name));
				if (spl_object_hash($ent) !== spl_object_hash($entity)) {
					$violations->add('name', 'The name is already in use.');
				}
			} catch (NotFound $e) {

			}
		}

		if (!in_array($entity->getType(), Command::getTypes())) {
			$violations->add('type', 'Invalid type.');
		}

		$line = $entity->getLine();
		if ($this->isEmpty($line)) {
			$violations->add('line', 'The command line cannot be empty.');
		} else {
			if (strpos($line, "\n") !== false) {
				$violations->add('line', 'The command cannot contain a new line.');
			}
			if (strpos($line, ';') !== false) {
				$violations->add('line', 'The command cannot contain `;` as that is considered a comment.');
			}
		}

		$expectedArgumentsCount = $entity->getLineArgumentsCount();
		$arguments = $entity->getArguments();

		if (count($arguments) !== $expectedArgumentsCount) {
			$violations->add('arguments', 'Command expects %expected% arguments. Got only %provided%.', array(
					'{{expected}}' => $expectedArgumentsCount,
					'{{provided}}' => count($arguments),
			));
		}

		foreach ($arguments as $argument) {
			$argumentId = $argument->getId();
			if ($argumentId === null || !preg_match('/^\$ARG([0-9]+)\$$/', $argumentId)) {
				$violations->add('arguments', 'Invalid command argument id: %id%', array('%id%' => $argumentId));
			}
			if ($this->isEmpty($argument->getDescription())) {
				$violations->add('arguments', 'Command argument descriptions cannot be empty.');
			}
		}

		return $violations;
	}

}
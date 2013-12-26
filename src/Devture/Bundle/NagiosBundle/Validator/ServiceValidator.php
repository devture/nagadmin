<?php
namespace Devture\Bundle\NagiosBundle\Validator;

use Devture\Bundle\SharedBundle\Validator\BaseValidator;
use Devture\Bundle\SharedBundle\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Repository\ServiceRepository;
use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Model\CommandArgument;

class ServiceValidator extends BaseValidator {

	private $repository;

	public function __construct(ServiceRepository $repository) {
		$this->repository = $repository;
	}

	/**
	 * @param Service $entity
	 * @param array $options
	 * @return \Devture\Bundle\SharedBundle\Validator\ViolationsList
	 */
	public function validate($entity, array $options = array()) {
		$violations = parent::validate($entity, $options);

		$name = $entity->getName();
		if (strlen($name) < 3 || !preg_match("/^[a-zA-Z0-9\s\-_\.]+$/", $name)) {
			$violations->add('name', 'Invalid name.');
		} else {
			try {
				$host = $entity->getHost();
				if ($host instanceof Host) {
					$ent = $this->repository->findOneBy(array('name' => $name, 'hostId' => $host->getId()));
					if (spl_object_hash($ent) !== spl_object_hash($entity)) {
						$violations->add('name', 'The name is already in use by another service on that host.');
					}
				}
			} catch (NotFound $e) {

			}
		}

		$expectedNotBlank = array(
			'maxCheckAttempts',
			'checkInterval', 'retryInterval',
			'notificationInterval',
		);
		foreach ($expectedNotBlank as $fieldName) {
			$getter = 'get' . ucfirst($fieldName);
			if ($this->isEmpty($entity->$getter())) {
				$violations->add($fieldName, 'This field cannot be blank.');
			}
		}

		$expectedNumeric = array(
			'maxCheckAttempts',
			'checkInterval', 'retryInterval',
			'notificationInterval',
		);
		foreach ($expectedNotBlank as $fieldName) {
			$getter = 'get' . ucfirst($fieldName);
			if (!is_numeric($entity->$getter())) {
				$violations->add($fieldName, 'This field should be numeric.');
			}
		}

		if (!($entity->getHost() instanceof Host)) {
			$violations->add('host', 'Invalid host.');
		}

		$command = $entity->getCommand();
		if (!($command instanceof Command) || $command->getType() !== Command::TYPE_SERVICE_CHECK) {
			$violations->add('command', 'Invalid command.');
		} else {
			$requiredArgumentIds = array_map(function (CommandArgument $arg) { return $arg->getId(); }, $command->getArguments());

			foreach ($entity->getArguments() as $argument) {
				$argumentId = $argument->getId();
				if (!in_array($argumentId, $requiredArgumentIds)) {
					$violations->add('arguments', 'Argument %id% is not valid for the selected command.', array('%id%' => $argumentId));
				} else {
					$argumentValue = $argument->getValue();
					if (strpos($argumentValue, '!') !== false) {
						$violations->add('arguments', 'Argument value `%value%` contains a ! character, which has a special meaning to Nagios and cannot be used.', array(
							'%value%' => $argumentValue,
						));
					} else if (strpos($argumentValue, "\n") !== false) {
						$violations->add('arguments', 'Arguments cannot contain new lines.');
					}
				}
			}

			foreach ($requiredArgumentIds as $argumentId) {
				if ($entity->getArgumentById($argumentId) === null) {
					$violations->add('arguments', 'Argument %id% is required by the command, but not specified.', array('%id%' => $argumentId));
				}
			}
		}

		return $violations;
	}

}
<?php
namespace Devture\Bundle\NagiosBundle\Validator;
use Devture\Bundle\SharedBundle\Validator\BaseValidator;
use Devture\Bundle\SharedBundle\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Repository\HostRepository;
use Devture\Bundle\NagiosBundle\Model\Host;

class HostValidator extends BaseValidator {

	private $repository;

	public function __construct(HostRepository $repository) {
		$this->repository = $repository;
	}

	public function validate(Host $entity, array $options = array()) {
		$violations = parent::validate($entity, $options);

		$regexName = "/^[a-z][a-z0-9_\-\.]+$/";

		$name = $entity->getName();
		if (strlen($name) < 3 || !preg_match($regexName, $name)) {
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

		if ($this->isEmpty($entity->getAddress())) {
			$violations->add('address', 'The address cannot be empty.');
		}

		foreach ($entity->getGroups() as $groupName) {
			if (!preg_match($regexName, $groupName)) {
				$violations->add('groups', 'The group name %name% is not valid.', array('%name%' => $groupName));
			}
		}

		return $violations;
	}

}
<?php
namespace Devture\Bundle\NagiosBundle\Validator;

use Devture\Component\Form\Validator\BaseValidator;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Repository\TimePeriodRepository;
use Devture\Bundle\NagiosBundle\Model\TimePeriod;

class TimePeriodValidator extends BaseValidator {

	private $repository;

	public function __construct(TimePeriodRepository $repository) {
		$this->repository = $repository;
	}

	/**
	 * @param TimePeriod $entity
	 * @param array $options
	 * @return \Devture\Component\Form\Validator\ViolationsList
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

		if ($this->isEmpty($entity->getTitle())) {
			$violations->add('title', 'The title cannot be empty.');
		}

		foreach ($entity->getRules() as $rule) {
			$dateRange = $rule->getDateRange();
			if ($this->isEmpty($dateRange)) {
				$violations->add('rules', 'Rule date range cannot be empty.');
				continue;
			}

			$timeRange = $rule->getTimeRange();
			if ($this->isEmpty($timeRange)) {
				$violations->add('rules', 'Rule time range cannot be empty.');
				continue;
			}
		}

		return $violations;
	}

}
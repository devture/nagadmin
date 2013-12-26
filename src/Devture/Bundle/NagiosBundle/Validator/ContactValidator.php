<?php
namespace Devture\Bundle\NagiosBundle\Validator;

use Devture\Bundle\SharedBundle\Validator\BaseValidator;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Repository\ContactRepository;
use Devture\Bundle\NagiosBundle\Model\Contact;
use Devture\Bundle\NagiosBundle\Model\TimePeriod;
use Devture\Bundle\NagiosBundle\Model\Command;

class ContactValidator extends BaseValidator {

	private $repository;

	public function __construct(ContactRepository $repository) {
		$this->repository = $repository;
	}

	/**
	 * @param Contact $entity
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

		if (!($entity->getTimePeriod() instanceof TimePeriod)) {
			$violations->add('timePeriod', 'The time period is not valid.');
		}

		if (!($entity->getServiceNotificationCommand() instanceof Command)) {
			$violations->add('serviceNotificationCommand', 'The service notification command is not valid.');
		}

		if ($this->isEmpty($entity->getEmail()) && count($entity->getAddresses()) === 0) {
			$violations->add('__other__', 'An email address or other addresses need to be entered.');
		}

		foreach ($entity->getAddresses() as $slot => $address) {
			$slot = (int)$slot;
			if ($slot < 1 || $slot > Contact::ADDRESS_SLOTS_COUNT) {
				$violations->add('addresses', 'Slot %slot% is not allowed.', array('%slot%' => $slot));
			}
		}

		return $violations;
	}

}
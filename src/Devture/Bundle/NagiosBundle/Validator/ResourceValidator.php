<?php
namespace Devture\Bundle\NagiosBundle\Validator;

use Devture\Bundle\SharedBundle\Validator\BaseValidator;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Model\Resource;

class ResourceValidator extends BaseValidator {

	/**
	 * @param Resource $entity
	 * @param array $options
	 * @return \Devture\Bundle\SharedBundle\Validator\ViolationsList
	 */
	public function validate($entity, array $options = array()) {
		$violations = parent::validate($entity, $options);

		foreach ($entity->getVariables() as $name => $value) {
			if (preg_match('/^\$USER([0-9]+)\$$/', $name, $matches)) {
				$slotNumber = (int)$matches[1];
				if ($slotNumber < 1 || $slotNumber > Resource::USER_VARIABLES_COUNT) {
					$violations->add('variables', 'Variable slot number for `%name%` is invalid.', array('%name%' => $name));
				}
			} else {
				$violations->add('variables', 'Variable name for `%name%` is invalid.', array('%name%' => $name));
			}
		}

		return $violations;
	}

}
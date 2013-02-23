<?php
namespace Devture\Bundle\NagiosBundle\Validator;

use Devture\Bundle\SharedBundle\Validator\BaseValidator;
use Devture\Bundle\SharedBundle\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Model\Resource;

class ResourceValidator extends BaseValidator {

	public function validate(Resource $entity, array $options = array()) {
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
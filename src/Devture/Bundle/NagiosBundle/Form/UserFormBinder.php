<?php
namespace Devture\Bundle\NagiosBundle\Form;

use Symfony\Component\HttpFoundation\Request;
use Devture\Bundle\NagiosBundle\Model\User;
use Devture\Bundle\NagiosBundle\Validator\NameValidator;

class UserFormBinder extends \Devture\Bundle\UserBundle\Form\FormBinder {

	/**
	 * @param User $entity
	 * @param Request $request
	 * @param array $options
	 */
	protected function doBindRequest($entity, Request $request, array $options = array()) {
		parent::doBindRequest($entity, $request, $options);

		//To avoid having to override the user validator as well, we'll do group validation during binding.
		$entity->clearGroups();
		foreach ((array)$request->request->get('groups') as $groupName) {
			if (NameValidator::isValid($groupName)) {
				$entity->addGroup($groupName);
			} else {
				$this->getViolations()->add('groups', 'The group name %name% is not valid.', array('%name%' => $groupName));
			}
		}
	}

}
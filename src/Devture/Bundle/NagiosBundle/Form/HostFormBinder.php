<?php
namespace Devture\Bundle\NagiosBundle\Form;

use Symfony\Component\HttpFoundation\Request;
use Devture\Bundle\SharedBundle\Form\SetterRequestBinder;
use Devture\Bundle\SharedBundle\Validator\BaseValidator;
use Devture\Bundle\NagiosBundle\Model\Host;

class HostFormBinder extends SetterRequestBinder {

	private $validator;

	public function __construct(BaseValidator $validator) {
		parent::__construct();
		$this->validator = $validator;
	}

	/**
	 * @param Host $entity
	 * @param Request $request
	 * @param array $options
	 */
	protected function doBindRequest($entity, Request $request, array $options = array()) {
		$whitelisted = array('name', 'address');
		$this->bindWhitelisted($entity, $request->request->all(), $whitelisted);

		$entity->clearGroups();
		foreach ((array)$request->request->get('groups') as $groupName) {
			$entity->addGroup($groupName);
		}

		$this->violations->merge($this->validator->validate($entity));
	}

}
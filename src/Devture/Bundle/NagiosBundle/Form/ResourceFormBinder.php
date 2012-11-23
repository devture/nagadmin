<?php
namespace Devture\Bundle\NagiosBundle\Form;
use Symfony\Component\HttpFoundation\Request;
use Devture\Bundle\SharedBundle\Form\SetterRequestBinder;
use Devture\Bundle\SharedBundle\Validator\BaseValidator;
use Devture\Bundle\NagiosBundle\Model\Resource;

class ResourceFormBinder extends SetterRequestBinder {

	private $validator;

	public function __construct(BaseValidator $validator) {
		parent::__construct();
		$this->validator = $validator;
	}

	protected function doBindRequest(Resource $entity, Request $request, array $options = array()) {
		$entity->clearVariables();
		foreach ((array)$request->request->get('variables') as $variableName => $variableValue) {
			if ($variableValue) {
				$entity->setVariable($variableName, $variableValue);
			}
		}

		$this->violations->merge($this->validator->validate($entity));
	}

}
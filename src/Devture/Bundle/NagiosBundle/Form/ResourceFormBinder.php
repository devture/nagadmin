<?php
namespace Devture\Bundle\NagiosBundle\Form;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\Form\Binder\SetterRequestBinder;
use Devture\Bundle\NagiosBundle\Model\Resource;

class ResourceFormBinder extends SetterRequestBinder {

	/**
	 * @param Resource $entity
	 * @param Request $request
	 * @param array $options
	 */
	protected function doBindRequest($entity, Request $request, array $options = array()) {
		$entity->clearVariables();
		foreach ((array)$request->request->get('variables') as $variableName => $variableValue) {
			if ($variableValue) {
				$entity->setVariable($variableName, $variableValue);
			}
		}
	}

}
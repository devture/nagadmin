<?php
namespace Devture\Bundle\NagiosBundle\Form;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\Form\Binder\SetterRequestBinder;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Model\CommandArgument;

class CommandFormBinder extends SetterRequestBinder {

	/**
	 * @param Command $entity
	 * @param Request $request
	 * @param array $options
	 */
	protected function doBindRequest($entity, Request $request, array $options = array()) {
		$whitelisted = array('name', 'title', 'line', 'type');
		$this->bindWhitelisted($entity, $request->request->all(), $whitelisted);

		$expectedArgumentsCount = $entity->getLineArgumentsCount();
		$entity->clearArguments();
		$argumentNumber = 0;
		foreach ($request->request->get('arguments') as $argumentData) {
			$argumentNumber += 1;
			if ($argumentNumber > $expectedArgumentsCount) {
				break;
			}
			$argument = new CommandArgument($argumentData);
			$argument->setId('$ARG' . $argumentNumber . '$');
			$entity->addArgument($argument);
		}
	}

}
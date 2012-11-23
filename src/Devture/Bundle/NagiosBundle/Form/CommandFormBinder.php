<?php
namespace Devture\Bundle\NagiosBundle\Form;
use Symfony\Component\HttpFoundation\Request;
use Devture\Bundle\SharedBundle\Form\SetterRequestBinder;
use Devture\Bundle\SharedBundle\Validator\BaseValidator;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Model\CommandArgument;

class CommandFormBinder extends SetterRequestBinder {

	private $validator;

	public function __construct(BaseValidator $validator) {
		parent::__construct();
		$this->validator = $validator;
	}

	protected function doBindRequest(Command $entity, Request $request, array $options = array()) {
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

		$this->violations->merge($this->validator->validate($entity));
	}

}
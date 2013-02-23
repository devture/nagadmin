<?php
namespace Devture\Bundle\NagiosBundle\Form;

use Symfony\Component\HttpFoundation\Request;
use Devture\Bundle\SharedBundle\Form\SetterRequestBinder;
use Devture\Bundle\SharedBundle\Validator\BaseValidator;
use Devture\Bundle\NagiosBundle\Model\TimePeriod;
use Devture\Bundle\NagiosBundle\Model\TimePeriodRule;

class TimePeriodFormBinder extends SetterRequestBinder {

	private $validator;

	public function __construct(BaseValidator $validator) {
		parent::__construct();
		$this->validator = $validator;
	}

	protected function doBindRequest(TimePeriod $entity, Request $request, array $options = array()) {
		$whitelisted = array('name', 'title');
		$this->bindWhitelisted($entity, $request->request->all(), $whitelisted);

		$entity->clearRules();
		foreach ((array)$request->request->get('rules') as $ruleData) {
			$entity->addRule(new TimePeriodRule($ruleData));
		}

		$this->violations->merge($this->validator->validate($entity));
	}

}
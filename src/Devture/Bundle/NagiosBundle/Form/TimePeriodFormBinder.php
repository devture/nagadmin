<?php
namespace Devture\Bundle\NagiosBundle\Form;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\Form\Binder\SetterRequestBinder;
use Devture\Bundle\NagiosBundle\Model\TimePeriod;
use Devture\Bundle\NagiosBundle\Model\TimePeriodRule;

class TimePeriodFormBinder extends SetterRequestBinder {

	/**
	 * @param TimePeriod $entity
	 * @param Request $request
	 * @param array $options
	 */
	protected function doBindRequest($entity, Request $request, array $options = array()) {
		$whitelisted = array('name', 'title');
		$this->bindWhitelisted($entity, $request->request->all(), $whitelisted);

		$entity->clearRules();
		foreach ((array)$request->request->get('rules') as $ruleData) {
			$entity->addRule(new TimePeriodRule($ruleData));
		}
	}

}
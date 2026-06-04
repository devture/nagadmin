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
		// `rules` is an array of {dateRange, timeRange}. Symfony's InputBag::get()
		// rejects non-scalar values, so the array is read via all().
		foreach ($request->request->all('rules') as $ruleData) {
			$entity->addRule(new TimePeriodRule($ruleData));
		}
	}

}
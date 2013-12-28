<?php
namespace Devture\Bundle\NagiosBundle\Form;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\Form\Binder\SetterRequestBinder;
use Devture\Bundle\NagiosBundle\Model\Host;

class HostFormBinder extends SetterRequestBinder {

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
	}

}
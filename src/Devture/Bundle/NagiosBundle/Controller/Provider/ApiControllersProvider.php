<?php
namespace Devture\Bundle\NagiosBundle\Controller\Provider;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Devture\Bundle\NagiosBundle\Model\Command;

class ApiControllersProvider implements ControllerProviderInterface {

	public function connect(Application $app) {
		$controllers = $app['controllers_factory'];

		$namespace = 'devture_nagios';

		$types = array_merge(Command::getTypes(), array('__TYPE__'));
		$controllers->get('/commands/{type}', 'devture_nagios.controller.command.api:listAction')
			->assert('type', implode('|', $types))
			->convert('type', function ($type) {
				return ($type === '__TYPE__' ? Command::TYPE_SERVICE_CHECK : $type);
			})
			->bind($namespace . '.api.command.list');

		$controllers->get('/hosts-info/{id}', 'devture_nagios.controller.host.api:infoAction')
			->value('id', null)
			->bind($namespace . '.api.host.info');
		$controllers->post('/host/recheck-services/{id}/{recheckType}/{token}', 'devture_nagios.controller.host.api:recheckServicesAction')
			->assert('recheckType', 'all|failing|__RECHECK_TYPE__')
			->convert('recheckType', function ($type) {
				return ($type === '__RECHECK_TYPE__' ? 'all' : $type);
			})
			->bind($namespace . '.api.host.recheck_services');

		return $controllers;
	}

}
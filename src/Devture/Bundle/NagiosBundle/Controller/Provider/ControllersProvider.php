<?php
namespace Devture\Bundle\NagiosBundle\Controller\Provider;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Devture\Bundle\NagiosBundle\Model\Command;

class ControllersProvider implements ControllerProviderInterface {

	public function connect(Application $app) {
		$controllers = $app['controllers_factory'];

		$namespace = 'devture_nagios';

		$controllers->get('/time-period/manage', 'devture_nagios.controller.time_period.management:indexAction')
			->bind($namespace . '.time_period.manage');
		$controllers->match('/time-period/add', 'devture_nagios.controller.time_period.management:addAction')
			->method('GET|POST')->bind($namespace . '.time_period.add');
		$controllers->match('/time-period/edit/{id}', 'devture_nagios.controller.time_period.management:editAction')
			->method('GET|POST')->bind($namespace . '.time_period.edit');
		$controllers->post('/time-period/delete/{id}/{token}', 'devture_nagios.controller.time_period.management:deleteAction')
			->bind($namespace . '.time_period.delete');

		$controllers->get('/command/manage/{type}', 'devture_nagios.controller.command.management:indexAction')
			->bind($namespace . '.command.manage')->value('type', Command::TYPE_SERVICE_CHECK);
		$controllers->match('/command/add/{type}', 'devture_nagios.controller.command.management:addAction')
			->method('GET|POST')->bind($namespace . '.command.add');
		$controllers->match('/command/edit/{id}', 'devture_nagios.controller.command.management:editAction')
			->method('GET|POST')->bind($namespace . '.command.edit');
		$controllers->post('/command/delete/{id}/{token}', 'devture_nagios.controller.command.management:deleteAction')
			->bind($namespace . '.command.delete');

		$controllers->get('/contact/manage', 'devture_nagios.controller.contact.management:indexAction')
			->bind($namespace . '.contact.manage');
		$controllers->match('/contact/add', 'devture_nagios.controller.contact.management:addAction')
			->method('GET|POST')->bind($namespace . '.contact.add');
		$controllers->match('/contact/edit/{id}', 'devture_nagios.controller.contact.management:editAction')
			->method('GET|POST')->bind($namespace . '.contact.edit');
		$controllers->post('/contact/delete/{id}/{token}', 'devture_nagios.controller.contact.management:deleteAction')
			->bind($namespace . '.contact.delete');

		$controllers->get('/host/manage', 'devture_nagios.controller.host.management:indexAction')
			->bind($namespace . '.host.manage');
		$controllers->match('/host/add', 'devture_nagios.controller.host.management:addAction')
			->method('GET|POST')->bind($namespace . '.host.add');
		$controllers->match('/host/edit/{id}', 'devture_nagios.controller.host.management:editAction')
			->method('GET|POST')->bind($namespace . '.host.edit');
		$controllers->post('/host/delete/{id}/{token}', 'devture_nagios.controller.host.management:deleteAction')
			->bind($namespace . '.host.delete');

		$controllers->get('/service/manage', 'devture_nagios.controller.service.management:indexAction')
			->bind($namespace . '.service.manage');
		$controllers->match('/service/add/{commandId}', 'devture_nagios.controller.service.management:addAction')
			->method('GET|POST')->bind($namespace . '.service.add');
		$controllers->match('/service/edit/{id}', 'devture_nagios.controller.service.management:editAction')
			->method('GET|POST')->bind($namespace . '.service.edit');
		$controllers->get('/service/view/{id}', 'devture_nagios.controller.service.management:viewAction')
			->bind($namespace . '.service.view');
		$controllers->post('/service/schedule_check/{id}/{token}', 'devture_nagios.controller.service.management:scheduleCheckAction')
			->bind($namespace . '.service.schedule_check');
		$controllers->post('/service/delete/{id}/{token}', 'devture_nagios.controller.service.management:deleteAction')
			->bind($namespace . '.service.delete');

		$controllers->get('/configuration/test', 'devture_nagios.controller.configuration.management:testAction')
			->bind($namespace . '.configuration.test');
		$controllers->post('/configuration/deploy', 'devture_nagios.controller.configuration.management:deployAction')
			->bind($namespace . '.configuration.deploy');

		$controllers->match('/resource/manage', 'devture_nagios.controller.resource.management:manageAction')
			->method('GET|POST')->bind($namespace . '.resource.manage');

		$controllers->get('/log/manage', 'devture_nagios.controller.log.management:manageAction')
			->bind($namespace . '.log.manage');

		$controllers->get('/dashboard', 'devture_nagios.controller.dashboard:dashboardAction')
			->bind($namespace . '.dashboard');

		return $controllers;
	}

}
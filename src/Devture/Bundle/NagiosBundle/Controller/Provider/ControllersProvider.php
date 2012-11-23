<?php
namespace Devture\Bundle\NagiosBundle\Controller\Provider;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Controller\TimePeriodManagementController;
use Devture\Bundle\NagiosBundle\Controller\CommandManagementController;
use Devture\Bundle\NagiosBundle\Controller\ContactManagementController;
use Devture\Bundle\NagiosBundle\Controller\HostManagementController;
use Devture\Bundle\NagiosBundle\Controller\ServiceManagementController;
use Devture\Bundle\NagiosBundle\Controller\ConfigurationManagementController;
use Devture\Bundle\NagiosBundle\Controller\ResourceManagementController;

class ControllersProvider implements ControllerProviderInterface {

	public function connect(Application $app) {
		$controllers = $app['controllers_factory'];

		$namespace = 'devture_nagios';

		$management = new TimePeriodManagementController($app, $namespace);
		$controllers->get('/time-period/manage', array($management, 'indexAction'))->bind($namespace . '.time_period.manage');
		$controllers->match('/time-period/add', array($management, 'addAction'))->method('GET|POST')->bind($namespace . '.time_period.add');
		$controllers->match('/time-period/edit/{id}', array($management, 'editAction'))->method('GET|POST')->bind($namespace . '.time_period.edit');
		$controllers->post('/time-period/delete/{id}/{token}', array(
				$management,
				'deleteAction'))->bind($namespace . '.time_period.delete');

		$management = new CommandManagementController($app, $namespace);
		$controllers->get('/command/manage/{type}', array($management, 'indexAction'))->bind($namespace . '.command.manage')->value('type', Command::TYPE_SERVICE_CHECK);
		$controllers->match('/command/add/{type}', array($management, 'addAction'))->method('GET|POST')->bind($namespace . '.command.add');
		$controllers->match('/command/edit/{id}', array($management, 'editAction'))->method('GET|POST')->bind($namespace . '.command.edit');
		$controllers->post('/command/delete/{id}/{token}', array(
				$management,
				'deleteAction'))->bind($namespace . '.command.delete');

		$management = new ContactManagementController($app, $namespace);
		$controllers->get('/contact/manage', array($management, 'indexAction'))->bind($namespace . '.contact.manage');
		$controllers->match('/contact/add', array($management, 'addAction'))->method('GET|POST')->bind($namespace . '.contact.add');
		$controllers->match('/contact/edit/{id}', array($management, 'editAction'))->method('GET|POST')->bind($namespace . '.contact.edit');
		$controllers->post('/contact/delete/{id}/{token}', array(
				$management,
				'deleteAction'))->bind($namespace . '.contact.delete');

		$management = new HostManagementController($app, $namespace);
		$controllers->get('/host/manage', array($management, 'indexAction'))->bind($namespace . '.host.manage');
		$controllers->match('/host/add', array($management, 'addAction'))->method('GET|POST')->bind($namespace . '.host.add');
		$controllers->match('/host/edit/{id}', array($management, 'editAction'))->method('GET|POST')->bind($namespace . '.host.edit');
		$controllers->post('/host/delete/{id}/{token}', array(
				$management,
				'deleteAction'))->bind($namespace . '.host.delete');

		$management = new ServiceManagementController($app, $namespace);
		$controllers->get('/service/manage', array($management, 'indexAction'))->bind($namespace . '.service.manage');
		$controllers->match('/service/add/{commandId}', array($management, 'addAction'))->method('GET|POST')->bind($namespace . '.service.add');
		$controllers->match('/service/edit/{id}', array($management, 'editAction'))->method('GET|POST')->bind($namespace . '.service.edit');
		$controllers->post('/service/delete/{id}/{token}', array(
				$management,
				'deleteAction'))->bind($namespace . '.service.delete');

		$management = new ConfigurationManagementController($app, $namespace);
		$controllers->get('/configuration/test', array($management, 'testAction'))->bind($namespace . '.configuration.test');
		$controllers->post('/configuration/deploy', array($management, 'deployAction'))->bind($namespace . '.configuration.deploy');

		$management = new ResourceManagementController($app, $namespace);
		$controllers->match('/resource/manage', array($management, 'manageAction'))->method('GET|POST')->bind($namespace . '.resource.manage');

		return $controllers;
	}

}
<?php
namespace Devture\Bundle\NagiosBundle;

use Silex\Application;

class ServicesProvider implements \Pimple\ServiceProviderInterface, \Silex\Api\BootableProviderInterface {

	private $config;

	public function __construct(array $config) {
		$this->config = $config;
	}

	public function register(\Pimple\Container $container) {
		$config = $this->config;

		$container['devture_nagios.bundle_path'] = dirname(__FILE__);

		$container['devture_nagios.nagios_url'] = $config['nagios_url'];

		$container['devture_nagios.colors'] = array('#014de7', '#3a87ad', '#06cf99', '#8fcf06', '#dda808', '#e76d01', '#7801e7', '#353535', '#888888',);

		$container['devture_nagios.db'] = function ($container) use ($config) {
			return $container[$config['database_service_id']];
		};

		$container['devture_nagios.event_dispatcher'] = function ($container) {
			$dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
			foreach ($container['devture_nagios.event_subscribers'] as $subscriber) {
				$dispatcher->addSubscriber($subscriber);
			}
			return $dispatcher;
		};

		$container['devture_nagios.event_subscribers'] = function ($container) {
			return array(
				$container['devture_nagios.time_period.event_subscriber'],
				$container['devture_nagios.command.event_subscriber'],
				$container['devture_nagios.host.event_subscriber'],
				$container['devture_nagios.contact.event_subscriber'],
			);
		};

		$container['devture_nagios.time_period.event_subscriber'] = function ($container) {
			return new Event\Subscriber\TimePeriodEventsSubscriber($container);
		};

		$container['devture_nagios.time_period.repository'] = function ($container) {
			return new Repository\TimePeriodRepository($container['devture_nagios.event_dispatcher'], $container['devture_nagios.db']);
		};

		$container['devture_nagios.time_period.validator'] = function ($container) {
			return new Validator\TimePeriodValidator($container['devture_nagios.time_period.repository']);
		};

		$container['devture_nagios.time_period.form_binder'] = function ($container) {
			$binder = new Form\TimePeriodFormBinder($container['devture_nagios.time_period.validator']);
			$binder->setCsrfProtection($container['devture_framework.csrf_token_manager'], 'time_period');
			return $binder;
		};

		$container['devture_nagios.command.event_subscriber'] = function ($container) {
			return new Event\Subscriber\CommandEventsSubscriber($container);
		};

		$container['devture_nagios.command.repository'] = function ($container) {
			return new Repository\CommandRepository($container['devture_nagios.event_dispatcher'], $container['devture_nagios.db']);
		};

		$container['devture_nagios.command.validator'] = function ($container) {
			return new Validator\CommandValidator($container['devture_nagios.command.repository']);
		};

		$container['devture_nagios.command.form_binder'] = function ($container) {
			$binder = new Form\CommandFormBinder($container['devture_nagios.command.validator']);
			$binder->setCsrfProtection($container['devture_framework.csrf_token_manager'], 'command');
			return $binder;
		};

		$container['devture_nagios.contact.event_subscriber'] = function ($container) {
			return new Event\Subscriber\ContactEventsSubscriber($container);
		};

		$container['devture_nagios.contact.repository'] = function ($container) {
			return new Repository\ContactRepository(
				$container['devture_nagios.event_dispatcher'],
				$container['devture_nagios.time_period.repository'],
				$container['devture_nagios.command.repository'],
				$container['devture_user.repository'],
				$container['devture_nagios.db']
			);
		};

		$container['devture_nagios.contact.validator'] = function ($container) {
			return new Validator\ContactValidator($container['devture_nagios.contact.repository']);
		};

		$container['devture_nagios.contact.form_binder'] = function ($container) {
			$binder = new Form\ContactFormBinder(
				$container['devture_nagios.time_period.repository'],
				$container['devture_nagios.command.repository'],
				$container['devture_user.repository'],
				$container['devture_nagios.helper.access_checker'],
				$container['devture_user.access_control'],
				$container['devture_nagios.contact.validator']
			);
			$binder->setCsrfProtection($container['devture_framework.csrf_token_manager'], 'contact');
			return $binder;
		};

		$container['devture_nagios.host.event_subscriber'] = function ($container) {
			return new Event\Subscriber\HostEventsSubscriber($container);
		};

		$container['devture_nagios.host.repository'] = function ($container) {
			return new Repository\HostRepository($container['devture_nagios.event_dispatcher'], $container['devture_nagios.db']);
		};

		$container['devture_nagios.host.validator'] = function ($container) {
			return new Validator\HostValidator($container['devture_nagios.host.repository']);
		};

		$container['devture_nagios.host.form_binder'] = function ($container) {
			$binder = new Form\HostFormBinder($container['devture_nagios.host.validator']);
			$binder->setCsrfProtection($container['devture_framework.csrf_token_manager'], 'host');
			return $binder;
		};

		$container['devture_nagios.service.defaults'] = new \ArrayObject($config['defaults']['service']);

		$container['devture_nagios.service.repository'] = function ($container) {
			return new Repository\ServiceRepository($container['devture_nagios.host.repository'], $container['devture_nagios.command.repository'], $container['devture_nagios.contact.repository'], $container['devture_nagios.db']);
		};

		$container['devture_nagios.service.validator'] = function ($container) {
			return new Validator\ServiceValidator($container['devture_nagios.service.repository']);
		};

		$container['devture_nagios.service.form_binder'] = function ($container) {
			$binder = new Form\ServiceFormBinder($container['devture_nagios.contact.repository'], $container['devture_nagios.service.validator']);
			$binder->setCsrfProtection($container['devture_framework.csrf_token_manager'], 'service');
			return $binder;
		};

		$container['devture_nagios.resource.repository'] = function ($container) {
			return new Repository\ResourceRepository($container['devture_nagios.db']);
		};

		$container['devture_nagios.resource.validator'] = function ($container) {
			return new Validator\ResourceValidator();
		};

		$container['devture_nagios.resource.form_binder'] = function ($container) {
			$binder = new Form\ResourceFormBinder($container['devture_nagios.resource.validator']);
			$binder->setCsrfProtection($container['devture_framework.csrf_token_manager'], 'resource');
			return $binder;
		};

		$container['devture_nagios.helper.colorizer'] = function ($container) {
			return new Helper\Colorizer($container['devture_nagios.colors']);
		};

		$container['devture_nagios.helper.access_checker'] = function ($container) {
			return new Helper\AccessChecker();
		};

		$container['devture_nagios.twig.extension'] = function ($container) {
			return new Twig\NagiosExtension($container);
		};

		$this->overrideUserServices($container);

		$this->registerDeploymentServices($container);

		$this->registerEmailServices($container);

		$this->registerSmsServices($container);

		$this->registerInstallerServices($container);

		$this->registerInteractionServices($container);

		$this->registerApiModelBridgeServices($container);

		$this->registerConsoleServices($container);

		$this->registerControllers($container, $config);
	}

	private function overrideUserServices(Application $container) {
		if (!isset($container['devture_user.repository'])) {
			throw new \LogicException('The NagiosBundle needs to be registered after the UserBundle, as it needs to override some of its services');
		}

		$container['devture_user.repository'] = function ($container) {
			return new Repository\UserRepository($container['devture_user.db']);
		};

		$container['devture_user.form_binder'] = function ($container) {
			$binder = new Form\UserFormBinder($container['devture_user.validator'], $container['devture_user.password_encoder']);
			$binder->setCsrfProtection($container['devture_framework.csrf_token_manager'], 'user');
			return $binder;
		};
	}

	private function registerConsoleServices(Application $container) {
		$config = $this->config;

		$container['devture_nagios.console.command.send_notification.email'] = function ($container) {
			return new ConsoleCommand\SendNotificationEmailCommand($container['devture_nagios.notification.email.sender_email_address'], $container);
		};

		$container['devture_nagios.console.command.send_notification.sms'] = function ($container) {
			return new ConsoleCommand\SendNotificationSmsCommand($container['devture_nagios.notification.sms.sender_id'], $container);
		};

		$container['devture_nagios.console.command.install'] = function ($container) {
			return new ConsoleCommand\InstallCommand($container);
		};

		$container['devture_nagios.console.command.check_status'] = function ($container) use ($config) {
			return new ConsoleCommand\CheckStatusCommand($config['status_file_path'], $container);
		};

		$container['devture_nagios.console.command.init_database'] = function ($container) {
			return new ConsoleCommand\InitDatabaseCommand($container);
		};
	}

	private function registerControllers(Application $container, array $config) {
		$container['devture_nagios.controllers_provider.management'] = function () {
			return new Controller\Provider\ControllersProvider();
		};

		$container['devture_nagios.controllers_provider.api'] = function () {
			return new Controller\Provider\ApiControllersProvider();
		};

		$container['devture_nagios.controller.time_period.management'] = function ($container) {
			return new Controller\TimePeriodManagementController($container, 'devture_nagios');
		};

		$container['devture_nagios.controller.command.management'] = function ($container) {
			return new Controller\CommandManagementController($container, 'devture_nagios');
		};
		$container['devture_nagios.controller.command.api'] = function ($container) {
			return new Controller\Api\CommandApiController($container, 'devture_nagios');
		};

		$container['devture_nagios.controller.contact.management'] = function ($container) {
			return new Controller\ContactManagementController($container, 'devture_nagios');
		};

		$container['devture_nagios.controller.host.management'] = function ($container) {
			return new Controller\HostManagementController($container, 'devture_nagios');
		};
		$container['devture_nagios.controller.host.api'] = function ($container) {
			return new Controller\Api\HostApiController($container, 'devture_nagios');
		};

		$container['devture_nagios.controller.service.management'] = function ($container) {
			return new Controller\ServiceManagementController($container, 'devture_nagios');
		};

		$container['devture_nagios.controller.configuration.management'] = function ($container) {
			return new Controller\ConfigurationManagementController($container, 'devture_nagios');
		};

		$container['devture_nagios.controller.resource.management'] = function ($container) {
			return new Controller\ResourceManagementController($container, 'devture_nagios');
		};

		$container['devture_nagios.controller.log.management'] = function ($container) {
			return new Controller\LogManagementController($container, 'devture_nagios');
		};
		$container['devture_nagios.controller.log.api'] = function ($container) {
			return new Controller\Api\LogApiController($container, 'devture_nagios');
		};

		$container['devture_nagios.controller.notification.api'] = function ($container) use ($config) {
			return new Controller\Api\NotificationApiController(
				$container,
				'devture_nagios',
				$config['notifications']['api_secret'],
				$container['devture_nagios.notification.sms.sender_id'],
				$container['devture_nagios.notification.email.sender_email_address'],
			);
		};

		$container['devture_nagios.controller.dashboard'] = function ($container) {
			return new Controller\DashboardController($container, 'devture_nagios');
		};

		$container['devture_nagios.public_routes'] = ['devture_nagios.api.notification.send_sms', 'devture_nagios.api.notification.send_email'];
	}

	private function registerDeploymentServices(Application $container) {
		$config = $this->config;

		$container['devture_nagios.deployment.exporter.internal'] = function ($container) {
			return new Deployment\Exporter\InternalConfigurationExporter();
		};

		$container['devture_nagios.deployment.exporter.resource_file'] = function ($container) {
			return new Deployment\Exporter\ResourceFileConfigurationExporter($container['devture_nagios.resource.repository']);
		};

		$container['devture_nagios.deployment.exporter.time_periods'] = function ($container) {
			return new Deployment\Exporter\TimePeriodsConfigurationExporter($container['devture_nagios.time_period.repository']);
		};

		$container['devture_nagios.deployment.exporter.contacts'] = function ($container) {
			return new Deployment\Exporter\ContactsConfigurationExporter($container['devture_nagios.contact.repository']);
		};

		$container['devture_nagios.deployment.exporter.commands'] = function ($container) {
			return new Deployment\Exporter\CommandsConfigurationExporter($container['devture_nagios.command.repository']);
		};

		$container['devture_nagios.deployment.exporter.host_groups'] = function ($container) {
			return new Deployment\Exporter\HostGroupsConfigurationExporter($container['devture_nagios.host.repository']);
		};

		$container['devture_nagios.deployment.exporter.hosts'] = function ($container) {
			return new Deployment\Exporter\HostsConfigurationExporter($container['devture_nagios.host.repository']);
		};

		$container['devture_nagios.deployment.exporter.services'] = function ($container) {
			return new Deployment\Exporter\ServicesConfigurationExporter($container['devture_nagios.service.repository']);
		};

		$container['devture_nagios.deployment.exporter.auto_service_deps'] = function ($container) use ($config) {
			$masterServiceRegexes = $config['auto_service_dependency']['master_service_regexes'];
			return new Deployment\Exporter\AutoServiceDepsConfigurationExporter($container['devture_nagios.host.repository'], $container['devture_nagios.service.repository'], $masterServiceRegexes);
		};

		$container['devture_nagios.deployment.configuration_collector'] = function ($container) {
			$collector = new Deployment\ConfigurationCollector();
			$collector->addExporter($container['devture_nagios.deployment.exporter.internal']);
			$collector->addExporter($container['devture_nagios.deployment.exporter.resource_file']);
			$collector->addExporter($container['devture_nagios.deployment.exporter.time_periods']);
			$collector->addExporter($container['devture_nagios.deployment.exporter.contacts']);
			$collector->addExporter($container['devture_nagios.deployment.exporter.commands']);
			$collector->addExporter($container['devture_nagios.deployment.exporter.host_groups']);
			$collector->addExporter($container['devture_nagios.deployment.exporter.hosts']);
			$collector->addExporter($container['devture_nagios.deployment.exporter.services']);
			$collector->addExporter($container['devture_nagios.deployment.exporter.auto_service_deps']);
			return $collector;
		};

		$container['devture_nagios.deployment.configuration_writer'] = function ($container) {
			return new Deployment\ConfigurationWriter();
		};

		$container['devture_nagios.deployment.configuration_tester'] = function ($container) {
			$writer = $container['devture_nagios.deployment.configuration_writer'];
			$mainFileTemplatePath = $container['devture_nagios.bundle_path'] . '/Resources/nagios_templates/nagios.cfg';
			return new Deployment\ConfigurationTester($writer, $mainFileTemplatePath);
		};

		$container['devture_nagios.deployment.handler'] = function ($container) use ($config) {
			$writer = $container['devture_nagios.deployment.configuration_writer'];
			$path = $config['deployment_handler']['path'];
			return new Deployment\Handler\DeploymentHandler($writer, $path, $container['devture_nagios.nagios_command.submitter']);
		};
	}

	private function registerEmailServices(Application $container) {
		$emailConfig = $this->config['notifications']['email'];

		$container['devture_nagios.notification.email.sender_email_address'] = $emailConfig['sender_email_address'];

		$container['devture_nagios.notification.email.transport.auth_handler'] = function () {
			return new \Swift_Transport_Esmtp_AuthHandler(array(
				new \Swift_Transport_Esmtp_Auth_CramMd5Authenticator(),
				new \Swift_Transport_Esmtp_Auth_LoginAuthenticator(),
				new \Swift_Transport_Esmtp_Auth_PlainAuthenticator(),
			));
		};

		$container['devture_nagios.notification.email.transport.buffer'] = function () {
			return new \Swift_Transport_StreamBuffer(new \Swift_StreamFilters_StringReplacementFilterFactory());
		};

		$container['devture_nagios.notification.email.transport.event_dispatcher'] = function () {
			return new \Swift_Events_SimpleEventDispatcher();
		};

		$container['devture_nagios.notification.email.transport'] = function ($container) use ($emailConfig) {
			$transport = new \Swift_Transport_EsmtpTransport(
					$container['devture_nagios.notification.email.transport.buffer'],
					array($container['devture_nagios.notification.email.transport.auth_handler']),
					$container['devture_nagios.notification.email.transport.event_dispatcher']
			);

			$transport->setHost($emailConfig['host']);
			$transport->setPort($emailConfig['port']);
			$transport->setUsername($emailConfig['username']);
			$transport->setPassword($emailConfig['password']);
			$transport->setEncryption($emailConfig['encryption']);
			$transport->setAuthMode($emailConfig['auth_mode']);

			return $transport;
		};

		$container['devture_nagios.notification.email.mailer'] = function ($container) {
			return new \Swift_Mailer($container['devture_nagios.notification.email.transport']);
		};
	}

	private function registerSmsServices(Application $container) {
		$smsConfig = $this->config['notifications']['sms'];

		$gatewayName = $smsConfig['gateway_name'];
		$username = $smsConfig['gateway_config']['username'];
		$password = $smsConfig['gateway_config']['password'];

		$container['devture_nagios.notification.sms.sender_id'] = $smsConfig['sender_id'];

		$container['devture_nagios.notification.sms.gateway.nexmo'] = function () use ($username, $password) {
			return new \Devture\Component\SmsSender\Gateway\NexmoGateway($username, $password);
		};

		$container['devture_nagios.notification.sms.gateway.bulksms'] = function () use ($username, $password) {
			return new \Devture\Component\SmsSender\Gateway\BulkSmsGateway($username, $password);
		};

		$container['devture_nagios.notification.sms.gateway'] = function ($container) use ($gatewayName) {
			if (!$gatewayName) {
				throw new \LogicException('Trying to use an SMS sender, but no SMS gateway is configured.');
			}

			$serviceId = 'devture_nagios.notification.sms.gateway.' . $gatewayName;

			if (!isset($container[$serviceId])) {
				throw new \InvalidArgumentException('Cannot find SMS gateway: ' . $gatewayName);
			}

			$gateway = $container[$serviceId];
			if (!($gateway instanceof \Devture\Component\SmsSender\Gateway\GatewayInterface)) {
				throw new \LogicException('The SMS gateway `' . $gatewayName . '` does not implement the required interface.');
			}
			return $gateway;
		};
	}

	private function registerInstallerServices(Application $container) {
		$config = $this->config;

		$container['devture_nagios.install.installer'] = function ($container) use ($config) {
			return new Install\Installer($container, $config['notifications']['api_secret']);
		};
	}

	private function registerInteractionServices(Application $container) {
		$config = $this->config;

		$container['devture_nagios.status.fetcher'] = function () use ($config) {
			return new Status\Fetcher($config['status_file_path']);
		};

		$container['devture_nagios.status.manager'] = function ($container) {
			return new Status\Manager($container['devture_nagios.status.fetcher']);
		};

		$container['devture_nagios.nagios_command.submitter'] = function () use ($config) {
			return new NagiosCommand\Submitter($config['command_file_path']);
		};

		$container['devture_nagios.nagios_command.manager'] = function ($container) {
			return new NagiosCommand\Manager($container['devture_nagios.nagios_command.submitter']);
		};

		$container['devture_nagios.log.fetcher'] = function ($container) use ($config) {
			return new Log\Fetcher($config['log_file_path'], $container['devture_nagios.host.repository'], $container['devture_nagios.service.repository']);
		};
	}

	private function registerApiModelBridgeServices(Application $container) {
		$container['devture_nagios.contact.api_model_bridge'] = function ($container) {
			return new ApiModelBridge\ContactBridge($container['devture_nagios.helper.colorizer']);
		};

		$container['devture_nagios.host.api_model_bridge'] = function ($container) {
			return new ApiModelBridge\HostBridge($container['devture_user.access_control'], $container['devture_nagios.helper.access_checker']);
		};

		$container['devture_nagios.host_info.api_model_bridge'] = function ($container) {
			return new ApiModelBridge\HostInfoBridge(
				$container['devture_nagios.host.api_model_bridge'],
				$container['devture_nagios.service_info.api_model_bridge']
			);
		};

		$container['devture_nagios.service.api_model_bridge'] = function ($container) {
			return new ApiModelBridge\ServiceBridge(
				$container['devture_nagios.host.api_model_bridge'],
				$container['devture_nagios.contact.api_model_bridge']
			);
		};

		$container['devture_nagios.service_status.api_model_bridge'] = function ($container) {
			return new ApiModelBridge\ServiceStatusBridge();
		};

		$container['devture_nagios.service_info.api_model_bridge'] = function ($container) {
			return new ApiModelBridge\ServiceInfoBridge(
				$container['devture_nagios.service.api_model_bridge'],
				$container['devture_nagios.service_status.api_model_bridge']
			);
		};

		$container['devture_nagios.command.api_model_bridge'] = function () {
			return new ApiModelBridge\CommandBridge();
		};

		$container['devture_nagios.log.api_model_bridge'] = function () {
			return new ApiModelBridge\LogBridge();
		};
	}

	public function boot(\Silex\Application $app) {
		if (isset($app['console'])) {
			$app['console']->add($app['devture_nagios.console.command.send_notification.email']);
			$app['console']->add($app['devture_nagios.console.command.send_notification.sms']);
			$app['console']->add($app['devture_nagios.console.command.install']);
			$app['console']->add($app['devture_nagios.console.command.check_status']);
			$app['console']->add($app['devture_nagios.console.command.init_database']);
		}

		$app['twig.loader.filesystem']->addPath(dirname(__FILE__) . '/Resources/views/');
		$app['twig']->addExtension($app['devture_nagios.twig.extension']);
	}

}

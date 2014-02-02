<?php
namespace Devture\Bundle\NagiosBundle;

use Silex\Application;
use Silex\ServiceProviderInterface;

class ServicesProvider implements ServiceProviderInterface {

	private $config;

	public function __construct(array $config) {
		$this->config = $config;
	}

	public function register(Application $app) {
		$config = $this->config;

		$app['devture_nagios.bundle_path'] = dirname(__FILE__);

		$app['devture_nagios.colors'] = array('#014de7', '#3a87ad', '#06cf99', '#8fcf06', '#dda808', '#e76d01', '#7801e7', '#353535', '#888888',);

		$app['devture_nagios.db'] = $app->share(function ($app) use ($config) {
			return $app[$config['database_service_id']];
		});

		$app['devture_nagios.event_dispatcher'] = $app->share(function ($app) {
			$dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
			foreach ($app['devture_nagios.event_subscribers'] as $subscriber) {
				$dispatcher->addSubscriber($subscriber);
			}
			return $dispatcher;
		});

		$app['devture_nagios.event_subscribers'] = function ($app) {
			return array(
				$app['devture_nagios.time_period.event_subscriber'],
				$app['devture_nagios.command.event_subscriber'],
				$app['devture_nagios.host.event_subscriber'],
				$app['devture_nagios.contact.event_subscriber'],
			);
		};

		$app['devture_nagios.time_period.event_subscriber'] = $app->share(function ($app) {
			return new Event\Subscriber\TimePeriodEventsSubscriber($app);
		});

		$app['devture_nagios.time_period.repository'] = $app->share(function ($app) {
			return new Repository\TimePeriodRepository($app['devture_nagios.event_dispatcher'], $app['devture_nagios.db']);
		});

		$app['devture_nagios.time_period.validator'] = function ($app) {
			return new Validator\TimePeriodValidator($app['devture_nagios.time_period.repository']);
		};

		$app['devture_nagios.time_period.form_binder'] = function ($app) {
			$binder = new Form\TimePeriodFormBinder($app['devture_nagios.time_period.validator']);
			$binder->setCsrfProtection($app['devture_framework.csrf_token_manager'], 'time_period');
			return $binder;
		};

		$app['devture_nagios.command.event_subscriber'] = $app->share(function ($app) {
			return new Event\Subscriber\CommandEventsSubscriber($app);
		});

		$app['devture_nagios.command.repository'] = $app->share(function ($app) {
			return new Repository\CommandRepository($app['devture_nagios.event_dispatcher'], $app['devture_nagios.db']);
		});

		$app['devture_nagios.command.validator'] = function ($app) {
			return new Validator\CommandValidator($app['devture_nagios.command.repository']);
		};

		$app['devture_nagios.command.form_binder'] = function ($app) {
			$binder = new Form\CommandFormBinder($app['devture_nagios.command.validator']);
			$binder->setCsrfProtection($app['devture_framework.csrf_token_manager'], 'command');
			return $binder;
		};

		$app['devture_nagios.contact.event_subscriber'] = $app->share(function ($app) {
			return new Event\Subscriber\ContactEventsSubscriber($app);
		});

		$app['devture_nagios.contact.repository'] = $app->share(function ($app) {
			return new Repository\ContactRepository(
				$app['devture_nagios.event_dispatcher'],
				$app['devture_nagios.time_period.repository'],
				$app['devture_nagios.command.repository'],
				$app['devture_user.repository'],
				$app['devture_nagios.db']
			);
		});

		$app['devture_nagios.contact.validator'] = function ($app) {
			return new Validator\ContactValidator($app['devture_nagios.contact.repository']);
		};

		$app['devture_nagios.contact.form_binder'] = function ($app) {
			$binder = new Form\ContactFormBinder(
				$app['devture_nagios.time_period.repository'],
				$app['devture_nagios.command.repository'],
				$app['devture_user.repository'],
				$app['devture_nagios.contact.validator']
			);
			$binder->setCsrfProtection($app['devture_framework.csrf_token_manager'], 'contact');
			return $binder;
		};

		$app['devture_nagios.host.event_subscriber'] = $app->share(function ($app) {
			return new Event\Subscriber\HostEventsSubscriber($app);
		});

		$app['devture_nagios.host.repository'] = $app->share(function ($app) {
			return new Repository\HostRepository($app['devture_nagios.event_dispatcher'], $app['devture_nagios.db']);
		});

		$app['devture_nagios.host.validator'] = function ($app) {
			return new Validator\HostValidator($app['devture_nagios.host.repository']);
		};

		$app['devture_nagios.host.form_binder'] = function ($app) {
			$binder = new Form\HostFormBinder($app['devture_nagios.host.validator']);
			$binder->setCsrfProtection($app['devture_framework.csrf_token_manager'], 'host');
			return $binder;
		};

		$app['devture_nagios.service.defaults'] = new \ArrayObject($config['defaults']['service']);

		$app['devture_nagios.service.repository'] = $app->share(function ($app) {
			return new Repository\ServiceRepository($app['devture_nagios.host.repository'], $app['devture_nagios.command.repository'], $app['devture_nagios.contact.repository'], $app['devture_nagios.db']);
		});

		$app['devture_nagios.service.validator'] = function ($app) {
			return new Validator\ServiceValidator($app['devture_nagios.service.repository']);
		};

		$app['devture_nagios.service.form_binder'] = function ($app) {
			$binder = new Form\ServiceFormBinder($app['devture_nagios.contact.repository'], $app['devture_nagios.service.validator']);
			$binder->setCsrfProtection($app['devture_framework.csrf_token_manager'], 'service');
			return $binder;
		};

		$app['devture_nagios.resource.repository'] = $app->share(function ($app) {
			return new Repository\ResourceRepository($app['devture_nagios.db']);
		});

		$app['devture_nagios.resource.validator'] = function ($app) {
			return new Validator\ResourceValidator();
		};

		$app['devture_nagios.resource.form_binder'] = function ($app) {
			$binder = new Form\ResourceFormBinder($app['devture_nagios.resource.validator']);
			$binder->setCsrfProtection($app['devture_framework.csrf_token_manager'], 'resource');
			return $binder;
		};

		$app['devture_nagios.helper.colorizer'] = $app->share(function ($app) {
			return new Helper\Colorizer($app['devture_nagios.colors']);
		});

		$app['devture_nagios.helper.access_checker'] = $app->share(function ($app) {
			return new Helper\AccessChecker();
		});

		$app['devture_nagios.twig.extension'] = function ($app) {
			return new Twig\NagiosExtension($app);
		};

		$this->overrideUserServices($app);

		$this->registerDeploymentServices($app);

		$this->registerEmailServices($app);

		$this->registerSmsServices($app);

		$this->registerInstallerServices($app);

		$this->registerInteractionServices($app);

		$this->registerApiModelBridgeServices($app);

		$this->registerConsoleServices($app);

		$this->registerControllers($app);
	}

	private function overrideUserServices(Application $app) {
		if (!isset($app['devture_user.repository'])) {
			throw new \LogicException('The NagiosBundle needs to be registered after the UserBundle, as it needs to override some of its services');
		}

		$app['devture_user.repository'] = $app->share(function ($app) {
			return new Repository\UserRepository($app['devture_user.db']);
		});

		$app['devture_user.form_binder'] = function ($app) {
			$binder = new Form\UserFormBinder($app['devture_user.validator'], $app['devture_user.password_encoder']);
			$binder->setCsrfProtection($app['devture_framework.csrf_token_manager'], 'user');
			return $binder;
		};
	}

	private function registerConsoleServices(Application $app) {
		$config = $this->config;

		$app['devture_nagios.console.command.send_notification.email'] = function ($app) {
			return new ConsoleCommand\SendNotificationEmailCommand($app['devture_nagios.notification.email.sender_email_address'], $app);
		};

		$app['devture_nagios.console.command.send_notification.sms'] = function ($app) {
			return new ConsoleCommand\SendNotificationSmsCommand($app['devture_nagios.notification.sms.sender_id'], $app);
		};

		$app['devture_nagios.console.command.install'] = function ($app) {
			return new ConsoleCommand\InstallCommand($app);
		};

		$app['devture_nagios.console.command.check_status'] = function ($app) use ($config) {
			return new ConsoleCommand\CheckStatusCommand($config['status_file_path'], $app);
		};
	}

	private function registerControllers(Application $app) {
		$app['devture_nagios.controllers_provider.management'] = $app->share(function () {
			return new Controller\Provider\ControllersProvider();
		});

		$app['devture_nagios.controllers_provider.api'] = $app->share(function () {
			return new Controller\Provider\ApiControllersProvider();
		});

		$app['devture_nagios.controller.time_period.management'] = function ($app) {
			return new Controller\TimePeriodManagementController($app, 'devture_nagios');
		};

		$app['devture_nagios.controller.command.management'] = function ($app) {
			return new Controller\CommandManagementController($app, 'devture_nagios');
		};
		$app['devture_nagios.controller.command.api'] = function ($app) {
			return new Controller\Api\CommandApiController($app, 'devture_nagios');
		};

		$app['devture_nagios.controller.contact.management'] = function ($app) {
			return new Controller\ContactManagementController($app, 'devture_nagios');
		};

		$app['devture_nagios.controller.host.management'] = function ($app) {
			return new Controller\HostManagementController($app, 'devture_nagios');
		};
		$app['devture_nagios.controller.host.api'] = function ($app) {
			return new Controller\Api\HostApiController($app, 'devture_nagios');
		};

		$app['devture_nagios.controller.service.management'] = function ($app) {
			return new Controller\ServiceManagementController($app, 'devture_nagios');
		};

		$app['devture_nagios.controller.configuration.management'] = function ($app) {
			return new Controller\ConfigurationManagementController($app, 'devture_nagios');
		};

		$app['devture_nagios.controller.resource.management'] = function ($app) {
			return new Controller\ResourceManagementController($app, 'devture_nagios');
		};

		$app['devture_nagios.controller.log.management'] = function ($app) {
			return new Controller\LogManagementController($app, 'devture_nagios');
		};
		$app['devture_nagios.controller.log.api'] = function ($app) {
			return new Controller\Api\LogApiController($app, 'devture_nagios');
		};

		$app['devture_nagios.controller.dashboard'] = function ($app) {
			return new Controller\DashboardController($app, 'devture_nagios');
		};
	}

	private function registerDeploymentServices(Application $app) {
		$config = $this->config;

		$app['devture_nagios.deployment.exporter.internal'] = $app->share(function ($app) {
			return new Deployment\Exporter\InternalConfigurationExporter();
		});

		$app['devture_nagios.deployment.exporter.resource_file'] = $app->share(function ($app) {
			return new Deployment\Exporter\ResourceFileConfigurationExporter($app['devture_nagios.resource.repository']);
		});

		$app['devture_nagios.deployment.exporter.time_periods'] = $app->share(function ($app) {
			return new Deployment\Exporter\TimePeriodsConfigurationExporter($app['devture_nagios.time_period.repository']);
		});

		$app['devture_nagios.deployment.exporter.contacts'] = $app->share(function ($app) {
			return new Deployment\Exporter\ContactsConfigurationExporter($app['devture_nagios.contact.repository']);
		});

		$app['devture_nagios.deployment.exporter.commands'] = $app->share(function ($app) {
			return new Deployment\Exporter\CommandsConfigurationExporter($app['devture_nagios.command.repository']);
		});

		$app['devture_nagios.deployment.exporter.host_groups'] = $app->share(function ($app) {
			return new Deployment\Exporter\HostGroupsConfigurationExporter($app['devture_nagios.host.repository']);
		});

		$app['devture_nagios.deployment.exporter.hosts'] = $app->share(function ($app) {
			return new Deployment\Exporter\HostsConfigurationExporter($app['devture_nagios.host.repository']);
		});

		$app['devture_nagios.deployment.exporter.services'] = $app->share(function ($app) {
			return new Deployment\Exporter\ServicesConfigurationExporter($app['devture_nagios.service.repository']);
		});

		$app['devture_nagios.deployment.exporter.auto_service_deps'] = $app->share(function ($app) use ($config) {
			$masterServiceRegexes = $config['auto_service_dependency']['master_service_regexes'];
			return new Deployment\Exporter\AutoServiceDepsConfigurationExporter($app['devture_nagios.host.repository'], $app['devture_nagios.service.repository'], $masterServiceRegexes);
		});

		$app['devture_nagios.deployment.configuration_collector'] = $app->share(function ($app) {
			$collector = new Deployment\ConfigurationCollector();
			$collector->addExporter($app['devture_nagios.deployment.exporter.internal']);
			$collector->addExporter($app['devture_nagios.deployment.exporter.resource_file']);
			$collector->addExporter($app['devture_nagios.deployment.exporter.time_periods']);
			$collector->addExporter($app['devture_nagios.deployment.exporter.contacts']);
			$collector->addExporter($app['devture_nagios.deployment.exporter.commands']);
			$collector->addExporter($app['devture_nagios.deployment.exporter.host_groups']);
			$collector->addExporter($app['devture_nagios.deployment.exporter.hosts']);
			$collector->addExporter($app['devture_nagios.deployment.exporter.services']);
			$collector->addExporter($app['devture_nagios.deployment.exporter.auto_service_deps']);
			return $collector;
		});

		$app['devture_nagios.deployment.configuration_writer'] = $app->share(function ($app) {
			return new Deployment\ConfigurationWriter();
		});

		$app['devture_nagios.deployment.configuration_tester'] = $app->share(function ($app) {
			$writer = $app['devture_nagios.deployment.configuration_writer'];
			$mainFileTemplatePath = $app['devture_nagios.bundle_path'] . '/Resources/nagios_templates/nagios.cfg';
			return new Deployment\ConfigurationTester($writer, $mainFileTemplatePath);
		});

		$app['devture_nagios.deployment.handler'] = $app->share(function ($app) use ($config) {
			$writer = $app['devture_nagios.deployment.configuration_writer'];
			$path = $config['deployment_handler']['path'];
			$cmd = $config['deployment_handler']['post_deployment_command'];
			return new Deployment\Handler\DeploymentHandler($writer, $path, $cmd);
		});
	}

	private function registerEmailServices(Application $app) {
		$emailConfig = $this->config['notifications']['email'];

		$app['devture_nagios.notification.email.sender_email_address'] = $emailConfig['sender_email_address'];

		$app['devture_nagios.notification.email.transport.auth_handler'] = $app->share(function () {
			return new \Swift_Transport_Esmtp_AuthHandler(array(
				new \Swift_Transport_Esmtp_Auth_CramMd5Authenticator(),
				new \Swift_Transport_Esmtp_Auth_LoginAuthenticator(),
				new \Swift_Transport_Esmtp_Auth_PlainAuthenticator(),
			));
		});

		$app['devture_nagios.notification.email.transport.buffer'] = $app->share(function () {
			return new \Swift_Transport_StreamBuffer(new \Swift_StreamFilters_StringReplacementFilterFactory());
		});

		$app['devture_nagios.notification.email.transport.event_dispatcher'] = $app->share(function () {
			return new \Swift_Events_SimpleEventDispatcher();
		});

		$app['devture_nagios.notification.email.transport'] = $app->share(function ($app) use ($emailConfig) {
			$transport = new \Swift_Transport_EsmtpTransport(
					$app['devture_nagios.notification.email.transport.buffer'],
					array($app['devture_nagios.notification.email.transport.auth_handler']),
					$app['devture_nagios.notification.email.transport.event_dispatcher']
			);

			$transport->setHost($emailConfig['host']);
			$transport->setPort($emailConfig['port']);
			$transport->setUsername($emailConfig['username']);
			$transport->setPassword($emailConfig['password']);
			$transport->setEncryption($emailConfig['encryption']);
			$transport->setAuthMode($emailConfig['auth_mode']);

			return $transport;
		});

		$app['devture_nagios.notification.email.mailer'] = $app->share(function ($app) {
			return new \Swift_Mailer($app['devture_nagios.notification.email.transport']);
		});
	}

	private function registerSmsServices(Application $app) {
		$smsConfig = $this->config['notifications']['sms'];

		$gatewayName = $smsConfig['gateway_name'];
		$username = $smsConfig['gateway_config']['username'];
		$password = $smsConfig['gateway_config']['password'];

		$app['devture_nagios.notification.sms.sender_id'] = $smsConfig['sender_id'];

		$app['devture_nagios.notification.sms.gateway.nexmo'] = $app->share(function () use ($username, $password) {
			return new \Devture\Component\SmsSender\Gateway\NexmoGateway($username, $password);
		});

		$app['devture_nagios.notification.sms.gateway.bulksms'] = $app->share(function () use ($username, $password) {
			return new \Devture\Component\SmsSender\Gateway\BulkSmsGateway($username, $password);
		});

		$app['devture_nagios.notification.sms.gateway'] = $app->share(function ($app) use ($gatewayName) {
			if (!$gatewayName) {
				throw new \LogicException('Trying to use an SMS sender, but no SMS gateway is configured.');
			}

			$serviceId = 'devture_nagios.notification.sms.gateway.' . $gatewayName;

			if (!isset($app[$serviceId])) {
				throw new \InvalidArgumentException('Cannot find SMS gateway: ' . $gatewayName);
			}

			$gateway = $app[$serviceId];
			if (!($gateway instanceof \Devture\Component\SmsSender\Gateway\GatewayInterface)) {
				throw new \LogicException('The SMS gateway `' . $gatewayName . '` does not implement the required interface.');
			}
			return $gateway;
		});
	}

	private function registerInstallerServices(Application $app) {
		$app['devture_nagios.install.installer'] = $app->share(function ($app) {
			return new Install\Installer($app);
		});
	}

	private function registerInteractionServices(Application $app) {
		$config = $this->config;

		$app['devture_nagios.status.fetcher'] = $app->share(function () use ($config) {
			return new Status\Fetcher($config['status_file_path']);
		});

		$app['devture_nagios.status.manager'] = $app->share(function ($app) {
			return new Status\Manager($app['devture_nagios.status.fetcher']);
		});

		$app['devture_nagios.nagios_command.submitter'] = $app->share(function () use ($config) {
			return new NagiosCommand\Submitter($config['command_file_path']);
		});

		$app['devture_nagios.nagios_command.manager'] = $app->share(function ($app) {
			return new NagiosCommand\Manager($app['devture_nagios.nagios_command.submitter']);
		});

		$app['devture_nagios.log.fetcher'] = $app->share(function ($app) use ($config) {
			return new Log\Fetcher($config['log_file_path'], $app['devture_nagios.host.repository'], $app['devture_nagios.service.repository']);
		});
	}

	private function registerApiModelBridgeServices(Application $app) {
		$app['devture_nagios.contact.api_model_bridge'] = $app->share(function ($app) {
			return new ApiModelBridge\ContactBridge($app['devture_nagios.helper.colorizer']);
		});

		$app['devture_nagios.host.api_model_bridge'] = $app->share(function ($app) {
			return new ApiModelBridge\HostBridge($app['devture_user.access_control'], $app['devture_nagios.helper.access_checker']);
		});

		$app['devture_nagios.host_info.api_model_bridge'] = $app->share(function ($app) {
			return new ApiModelBridge\HostInfoBridge(
				$app['devture_nagios.host.api_model_bridge'],
				$app['devture_nagios.service_info.api_model_bridge']
			);
		});

		$app['devture_nagios.service.api_model_bridge'] = $app->share(function ($app) {
			return new ApiModelBridge\ServiceBridge(
				$app['devture_nagios.host.api_model_bridge'],
				$app['devture_nagios.contact.api_model_bridge']
			);
		});

		$app['devture_nagios.service_status.api_model_bridge'] = $app->share(function ($app) {
			return new ApiModelBridge\ServiceStatusBridge();
		});

		$app['devture_nagios.service_info.api_model_bridge'] = $app->share(function ($app) {
			return new ApiModelBridge\ServiceInfoBridge(
				$app['devture_nagios.service.api_model_bridge'],
				$app['devture_nagios.service_status.api_model_bridge']
			);
		});

		$app['devture_nagios.command.api_model_bridge'] = $app->share(function () {
			return new ApiModelBridge\CommandBridge();
		});

		$app['devture_nagios.log.api_model_bridge'] = $app->share(function () {
			return new ApiModelBridge\LogBridge();
		});
	}

	public function boot(Application $app) {
		if (isset($app['console'])) {
			$app['console']->add($app['devture_nagios.console.command.send_notification.email']);
			$app['console']->add($app['devture_nagios.console.command.send_notification.sms']);
			$app['console']->add($app['devture_nagios.console.command.install']);
			$app['console']->add($app['devture_nagios.console.command.check_status']);
		}

		$app['twig.loader.filesystem']->addPath(dirname(__FILE__) . '/Resources/views/');
		$app['twig']->addExtension($app['devture_nagios.twig.extension']);
	}

}

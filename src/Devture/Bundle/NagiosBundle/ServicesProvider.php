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

		//Wrap views in an array object, otherwise we won't be able to change them
		//as arrays are copied when returned from the container (and we need a reference).
		$app['devture_nagios.views'] = new \ArrayObject(array(
				'time_period.index' => 'DevtureNagiosBundle/time_period/index.html.twig',
				'time_period.list' => 'DevtureNagiosBundle/time_period/list.html.twig',
				'time_period.record' => 'DevtureNagiosBundle/time_period/record.html.twig',
				'time_period.rule_editor' => 'DevtureNagiosBundle/time_period/rule_editor.html.twig',
				'time_period.rules_table' => 'DevtureNagiosBundle/time_period/rules_table.html.twig',
				'command.index' => 'DevtureNagiosBundle/command/index.html.twig',
				'command.list' => 'DevtureNagiosBundle/command/list.html.twig',
				'command.record' => 'DevtureNagiosBundle/command/record.html.twig',
				'command.argument' => 'DevtureNagiosBundle/command/argument.html.twig',
				'contact.index' => 'DevtureNagiosBundle/contact/index.html.twig',
				'contact.list' => 'DevtureNagiosBundle/contact/list.html.twig',
				'contact.record' => 'DevtureNagiosBundle/contact/record.html.twig',
				'host.index' => 'DevtureNagiosBundle/host/index.html.twig',
				'host.list' => 'DevtureNagiosBundle/host/list.html.twig',
				'host.record' => 'DevtureNagiosBundle/host/record.html.twig',
				'service.index' => 'DevtureNagiosBundle/service/index.html.twig',
				'service.list' => 'DevtureNagiosBundle/service/list.html.twig',
				'service.record' => 'DevtureNagiosBundle/service/record.html.twig',
				'service.add_new_picker' => 'DevtureNagiosBundle/service/add_new_picker.html.twig',
				'configuration.test' => 'DevtureNagiosBundle/configuration/test.html.twig',
				'configuration.deploy' => 'DevtureNagiosBundle/configuration/deploy.html.twig',
				'resource.management' => 'DevtureNagiosBundle/resource/management.html.twig',
				'layout' => 'DevtureNagiosBundle/layout.html.twig',));

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

		$app['devture_nagios.time_period.repository'] = $app->share(function ($app) use ($config) {
			return new Repository\TimePeriodRepository($app['devture_nagios.event_dispatcher'], $app[$config['database_service_id']]);
		});

		$app['devture_nagios.time_period.validator'] = function ($app) {
			return new Validator\TimePeriodValidator($app['devture_nagios.time_period.repository']);
		};

		$app['devture_nagios.time_period.form_binder'] = function ($app) {
			$binder = new Form\TimePeriodFormBinder($app['devture_nagios.time_period.validator']);
			$binder->setCsrfProtection($app['shared.csrf_token_generator'], 'time_period');
			return $binder;
		};

		$app['devture_nagios.command.event_subscriber'] = $app->share(function ($app) {
			return new Event\Subscriber\CommandEventsSubscriber($app);
		});

		$app['devture_nagios.command.repository'] = $app->share(function ($app) use ($config) {
			return new Repository\CommandRepository($app['devture_nagios.event_dispatcher'], $app[$config['database_service_id']]);
		});

		$app['devture_nagios.command.validator'] = function ($app) {
			return new Validator\CommandValidator($app['devture_nagios.command.repository']);
		};

		$app['devture_nagios.command.form_binder'] = function ($app) {
			$binder = new Form\CommandFormBinder($app['devture_nagios.command.validator']);
			$binder->setCsrfProtection($app['shared.csrf_token_generator'], 'command');
			return $binder;
		};

		$app['devture_nagios.contact.event_subscriber'] = $app->share(function ($app) {
			return new Event\Subscriber\ContactEventsSubscriber($app);
		});

		$app['devture_nagios.contact.repository'] = $app->share(function ($app) use ($config) {
			return new Repository\ContactRepository($app['devture_nagios.event_dispatcher'], $app['devture_nagios.time_period.repository'], $app['devture_nagios.command.repository'], $app[$config['database_service_id']]);
		});

		$app['devture_nagios.contact.validator'] = function ($app) {
			return new Validator\ContactValidator($app['devture_nagios.contact.repository']);
		};

		$app['devture_nagios.contact.form_binder'] = function ($app) {
			$binder = new Form\ContactFormBinder($app['devture_nagios.time_period.repository'], $app['devture_nagios.command.repository'], $app['devture_nagios.contact.validator']);
			$binder->setCsrfProtection($app['shared.csrf_token_generator'], 'contact');
			return $binder;
		};

		$app['devture_nagios.host.event_subscriber'] = $app->share(function ($app) {
			return new Event\Subscriber\HostEventsSubscriber($app);
		});

		$app['devture_nagios.host.repository'] = $app->share(function ($app) use ($config) {
			return new Repository\HostRepository($app['devture_nagios.event_dispatcher'], $app[$config['database_service_id']]);
		});

		$app['devture_nagios.host.validator'] = function ($app) {
			return new Validator\HostValidator($app['devture_nagios.host.repository']);
		};

		$app['devture_nagios.host.form_binder'] = function ($app) {
			$binder = new Form\HostFormBinder($app['devture_nagios.host.validator']);
			$binder->setCsrfProtection($app['shared.csrf_token_generator'], 'host');
			return $binder;
		};

		$app['devture_nagios.service.defaults'] = new \ArrayObject($config['defaults']['service']);

		$app['devture_nagios.service.repository'] = $app->share(function ($app) use ($config) {
			return new Repository\ServiceRepository($app['devture_nagios.host.repository'], $app['devture_nagios.command.repository'], $app['devture_nagios.contact.repository'], $app[$config['database_service_id']]);
		});

		$app['devture_nagios.service.validator'] = function ($app) {
			return new Validator\ServiceValidator($app['devture_nagios.service.repository']);
		};

		$app['devture_nagios.service.form_binder'] = function ($app) {
			$binder = new Form\ServiceFormBinder($app['devture_nagios.host.repository'], $app['devture_nagios.contact.repository'], $app['devture_nagios.service.validator']);
			$binder->setCsrfProtection($app['shared.csrf_token_generator'], 'service');
			return $binder;
		};

		$app['devture_nagios.resource.repository'] = $app->share(function ($app) use ($config) {
			return new Repository\ResourceRepository($app[$config['database_service_id']]);
		});

		$app['devture_nagios.resource.validator'] = function ($app) {
			return new Validator\ResourceValidator();
		};

		$app['devture_nagios.resource.form_binder'] = function ($app) {
			$binder = new Form\ResourceFormBinder($app['devture_nagios.resource.validator']);
			$binder->setCsrfProtection($app['shared.csrf_token_generator'], 'resource');
			return $binder;
		};

		$this->registerDeploymentServices($app);

		$this->registerEmailServices($app);

		$this->registerSmsServices($app);

		$this->registerInstallerServices($app);

		$app['devture_nagios.controllers_provider.management'] = $app->share(function () {
			return new Controller\Provider\ControllersProvider();
		});
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

		$app['devture_nagios.twig.extension.colorize'] = function () {
			return new Twig\ColorizeExtension();
		};
	}

	private function registerInstallerServices(Application $app) {
		$app['devture_nagios.install.installer'] = $app->share(function ($app) {
			return new Install\Installer($app);
		});
	}

	public function boot(Application $app) {
		$app['twig.loader']->addLoader(new \Twig_Loader_Filesystem(dirname(__FILE__) . '/Resources/views/'));

		$app['twig']->addExtension($app['devture_nagios.twig.extension.colorize']);
	}

}

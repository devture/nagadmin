<?php
namespace Devture\Bundle\NagiosBundle\Deployment\Exporter;

use Devture\Bundle\NagiosBundle\Deployment\ConfigurationFile;
use Devture\Bundle\NagiosBundle\Deployment\ObjectDefinition;

class InternalConfigurationExporter implements ConfigurationExporterInterface {

	public function export() {
		$configurationFile = new ConfigurationFile('configuration/internal.cfg', ConfigurationFile::TYPE_CONFIGURATION_FILE);

		$definition = new ObjectDefinition('timeperiod');
		$definition->addDirective('timeperiod_name', 'nagadmin-24x7');
		$definition->addDirective('alias', 'nagadmin-24x7');
		foreach (array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') as $dayName) {
			$definition->addDirective($dayName, '00:00-24:00');
		}
		$configurationFile->addObjectDefinition($definition);

		$definition = new ObjectDefinition('command');
		$definition->addDirective('command_name', 'nagadmin-check-dummy-ok');
		$definition->addDirective('command_line', '$USER1$/check_dummy 0 "Nagadmin does not perform host checks. All hosts are forced to an OK status."');
		$configurationFile->addObjectDefinition($definition);

		$definition = new ObjectDefinition('command');
		$definition->addDirective('command_name', 'nagadmin-notify-dummy');
		$definition->addDirective('command_line', 'true');
		$configurationFile->addObjectDefinition($definition);

		$definition = new ObjectDefinition('contact');
		$definition->addDirective('name', 'nagadmin-contact');
		$definition->addDirective('host_notification_period', 'nagadmin-24x7');
		$definition->addDirective('host_notification_commands', 'nagadmin-notify-dummy');
		$definition->addDirective('service_notification_period', 'nagadmin-24x7');
		$definition->addDirective('service_notification_options', 'w,u,c,r,f,s');
		$definition->addDirective('host_notification_options', 'd,u,r,f,s');
		$definition->addDirective('register', 0); //Not a real contact, just a template. Do not really register!
		$configurationFile->addObjectDefinition($definition);

		//A fake/dummy contact to prevent Nagios from complaining when there are no real contacts
		//defined in the system (which is the initial state after installation).
		//Nagios should also run without contacts defined - if the notification features are not used.
		$definition = new ObjectDefinition('contact');
		$definition->addDirective('use', 'nagadmin-contact');
		$definition->addDirective('contact_name', 'nagadmin-dummy-contact');
		$definition->addDirective('email', 'root@localhost');
		$definition->addDirective('service_notification_commands', 'nagadmin-notify-dummy');
		$configurationFile->addObjectDefinition($definition);

		$definition = new ObjectDefinition('host');
		$definition->addDirective('name', 'nagadmin-host');
		$definition->addDirective('notifications_enabled', 1);
		$definition->addDirective('event_handler_enabled', 1);
		$definition->addDirective('flap_detection_enabled', 1);
		$definition->addDirective('failure_prediction_enabled', 1);
		$definition->addDirective('process_perf_data', 1);
		$definition->addDirective('retain_nonstatus_information', 1);
		$definition->addDirective('notification_period', 'nagadmin-24x7');
		$definition->addDirective('check_period', 'nagadmin-24x7');
		$definition->addDirective('check_command', 'nagadmin-check-dummy-ok');
		$definition->addDirective('max_check_attempts', 10);
		$definition->addDirective('register', 0); //Not a real host, just a template. Do not really register!
		$configurationFile->addObjectDefinition($definition);

		$definition = new ObjectDefinition('service');
		$definition->addDirective('name', 'nagadmin-service');
		$definition->addDirective('active_checks_enabled', 1);
		$definition->addDirective('passive_checks_enabled', 1);
		$definition->addDirective('parallelize_check', 1);
		$definition->addDirective('obsess_over_service', 0);
		$definition->addDirective('notifications_enabled', 1);
		$definition->addDirective('event_handler_enabled', 1);
		$definition->addDirective('flap_detection_enabled', 1);
		$definition->addDirective('failure_prediction_enabled', 1);
		$definition->addDirective('process_perf_data', 1);
		$definition->addDirective('retain_status_information', 1);
		$definition->addDirective('retain_nonstatus_information', 1);
		$definition->addDirective('is_volatile', 0);
		$definition->addDirective('check_period', 'nagadmin-24x7');
		$definition->addDirective('notification_period', 'nagadmin-24x7');
		$definition->addDirective('notification_options', 'w,u,c,r');
		$definition->addDirective('register', 0); //Not a real host, just a template. Do not really register!
		$configurationFile->addObjectDefinition($definition);

		return $configurationFile;
	}

}

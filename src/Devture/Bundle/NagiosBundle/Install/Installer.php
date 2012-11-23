<?php
namespace Devture\Bundle\NagiosBundle\Install;

use Devture\Bundle\NagiosBundle\Model\TimePeriod;

class Installer {

	private $container;

	public function __construct(\Pimple $container) {
		$this->container = $container;
	}

	public function get($serviceId) {
		return $this->container[$serviceId];
	}

	public function install() {
		$resource = $this->get('devture_nagios.resource.repository')->getResource();
		$resource->setVariable('$USER1$', $this->detectNagiosPluginsPath());
		$resource->setVariable('$USER2$', $this->get('app_base_path'));
		$this->get('devture_nagios.resource.repository')->update($resource);
	}

	private function detectNagiosPluginsPath() {
		$candidates = array(
			'/usr/share/nagios/libexec',
			'/usr/lib64/nagios/plugins',
			'/usr/lib/nagios/plugins',
		);

		foreach ($candidates as $path) {
			if (file_exists($path)) {
				return $path;
			}
		}

		return '/dev/null';
	}

}
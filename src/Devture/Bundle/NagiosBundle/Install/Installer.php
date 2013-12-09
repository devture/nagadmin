<?php
namespace Devture\Bundle\NagiosBundle\Install;

class Installer {

	private $container;

	public function __construct(\Pimple $container) {
		$this->container = $container;
	}

	public function install() {
		$resource = $this->getResourceRepository()->getResource();
		$resource->setVariable('$USER1$', $this->detectNagiosPluginsPath());
		$resource->setVariable('$USER2$', $this->getAppBasePath());
		$this->getResourceRepository()->update($resource);
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

	/**
	 * @return \Devture\Bundle\NagiosBundle\Repository\ResourceRepository
	 */
	private function getResourceRepository() {
		return $this->container['devture_nagios.resource.repository'];
	}

	private function getAppBasePath() {
		return $this->container['app_base_path'];
	}

}
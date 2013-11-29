<?php
namespace Devture\Bundle\NagiosBundle\Status;

use Devture\Bundle\NagiosBundle\Model\Service;

class Manager {

	private $fetcher;
	private $statusCache;

	public function __construct(Fetcher $fetcher) {
		$this->fetcher = $fetcher;
	}

	/**
	 * @param Service $service
	 * @return \Devture\Bundle\NagiosBundle\Status\ServiceStatus|NULL
	 */
	public function getServiceStatus(Service $service) {
		$this->load();

		$hostName = $service->getHost()->getName();
		$serviceDescription = $service->getName();

		foreach ($this->statusCache as $status) {
			if (!($status instanceof ServiceStatus)) {
				continue;
			}
			if ($status->getHostname() !== $hostName) {
				continue;
			}
			if ($status->getServiceDescription() === $serviceDescription) {
				return $status;
			}
		}

		return null;
	}

	private function load() {
		if ($this->statusCache === null) {
			$this->statusCache = $this->fetcher->fetch();
		}
	}

}
<?php
namespace Devture\Bundle\NagiosBundle\Status;

use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Exception\FileMissingException;

class Manager {

	private $fetcher;
	private $statusCache;

	public function __construct(Fetcher $fetcher) {
		$this->fetcher = $fetcher;
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Status\InfoStatus|NULL
	 */
	public function getInfoStatus() {
		$this->load();

		foreach ($this->statusCache as $status) {
			if ($status instanceof InfoStatus) {
				return $status;
			}
		}

		return null;
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Status\ProgramStatus|NULL
	 */
	public function getProgramStatus() {
		$this->load();

		foreach ($this->statusCache as $status) {
			if ($status instanceof ProgramStatus) {
				return $status;
			}
		}

		return null;
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Status\ServiceStatus[]
	 */
	public function getServicesStatus() {
		$this->load();

		return array_filter($this->statusCache, function (Status $status) {
			return ($status instanceof ServiceStatus);
		});
	}

	/**
	 * @param Service $service
	 * @return \Devture\Bundle\NagiosBundle\Status\ServiceStatus|NULL
	 */
	public function getServiceStatus(Service $service) {
		$hostName = $service->getHost()->getName();
		$serviceDescription = $service->getName();

		/* @var $status ServiceStatus */
		foreach ($this->getServicesStatus() as $status) {
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
			try {
				$this->statusCache = $this->fetcher->fetch();
			} catch (FileMissingException $e) {
				$this->statusCache = array();
			}
		}
	}

}
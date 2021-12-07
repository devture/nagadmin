<?php
namespace Devture\Bundle\NagiosBundle\Status;

use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Exception\FileMissingException;

class Manager {

	private $fetcher;

	private $statusLoaded = false;

	private $servicesStatusMap = array(); //identifier -> ServiceStatus

	/**
	 * @var InfoStatus|NULL
	 */
	private $infoStatus;

	/**
	 * @var ProgramStatus|NULL
	 */
	private $programStatus;

	public function __construct(Fetcher $fetcher) {
		$this->fetcher = $fetcher;
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Status\InfoStatus|NULL
	 */
	public function getInfoStatus() {
		$this->load();
		return $this->infoStatus;
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Status\ProgramStatus|NULL
	 */
	public function getProgramStatus() {
		$this->load();
		return $this->programStatus;
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Status\ServiceStatus[]
	 */
	public function getServicesStatus() {
		$this->load();
		return array_values($this->servicesStatusMap);
	}

	/**
	 * @param Service $service
	 * @return \Devture\Bundle\NagiosBundle\Status\ServiceStatus|NULL
	 */
	public function getServiceStatus(Service $service) {
		$this->load();
		$serviceIdentifier = $service->getHost()->getName() . '/' . $service->getName();
		return (isset($this->servicesStatusMap[$serviceIdentifier]) ? $this->servicesStatusMap[$serviceIdentifier] : null);
	}

	private function load() {
		if (!$this->statusLoaded) {
			$this->statusLoaded = true;

			//Try to load multiple times, with some wait time in-between,
			//because the status file may be missing while Nagios reloads itself (after deployment).
			//We don't want to fail in that cause, so let's try to handle such micro-problems
			//transparently at the cost of a slow `load()` when they occur.
			for ($i = 0; $i < 5; ++$i) {
				try {
					$this->doLoad();
					break;
				} catch (FileMissingException $e) {
					usleep(500000); //0.5 seconds
					shell_exec('echo "temporary failure" > /tmp/failures.txt');
				}
			}
		}
	}

	private function doLoad() {
		foreach ($this->fetcher->fetch() as $status) {
			if ($status instanceof ServiceStatus) {
				$serviceIdentifier = $status->getHostname() . '/' . $status->getServiceDescription();
				$this->servicesStatusMap[$serviceIdentifier] = $status;
			} else if ($status instanceof InfoStatus) {
				$this->infoStatus = $status;
			} else if ($status instanceof ProgramStatus) {
				$this->programStatus = $status;
			}
		}
	}

}

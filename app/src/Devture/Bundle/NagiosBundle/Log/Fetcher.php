<?php
namespace Devture\Bundle\NagiosBundle\Log;

use Devture\Bundle\NagiosBundle\Exception\ParseException;
use Devture\Bundle\NagiosBundle\Exception\FileMissingException;
use Devture\Bundle\NagiosBundle\Repository\ServiceRepository;
use Devture\Bundle\NagiosBundle\Repository\HostRepository;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Component\DBAL\Exception\NotFound;

class Fetcher {

	private string $logFilePath;
	private $hostRepository;
	private $serviceRepository;

	/**
	 * @var array<string, Host>|null
	 */
	private ?array $hostsMap = null;

	/**
	 * @var array<string, Service>|null
	 */
	private ?array $servicesMap = null;

	public function __construct(string $logFilePath, HostRepository $hostRepository, ServiceRepository $serviceRepository) {
		$this->logFilePath = $logFilePath;
		$this->hostRepository = $hostRepository;
		$this->serviceRepository = $serviceRepository;
	}

	/**
	 * @throws FileMissingException
	 * @return \Devture\Bundle\NagiosBundle\Log\LogEntry[]
	 */
	public function fetch() {
		if (!file_exists($this->logFilePath)) {
			throw new FileMissingException(sprintf('Cannot find log file at `%s`', $this->logFilePath));
		}
		return $this->parse((string) file_get_contents($this->logFilePath));
	}

	/**
	 * @return array<int, LogEntry>
	 */
	public function fetchForHost(Host $host) {
		return array_filter($this->fetch(), function (LogEntry $logEntry) use ($host) {
			return ($logEntry->getHost() === $host);
		});
	}

	/**
	 * @return array<int, LogEntry>
	 */
	public function fetchForService(Service $service) {
		return array_filter($this->fetch(), function (LogEntry $logEntry) use ($service) {
			return ($logEntry->getService() === $service);
		});
	}

	/**
	 * @return list<LogEntry>
	 */
	private function parse(string $contents) {
		$lines = explode("\n", $contents);

		$objects = array();
		foreach ($lines as $line) {
			if ($line === '') {
				continue;
			}

			//Lines have one of 2 possible formats:
			// 1. [{timestamp}] {type}: {text}
			// 2. [{timestamp}] {text}
			if (preg_match('/^\[(\d+)\]\s(((.+?):\s(.+))|(.+))$/', $line, $matches)) {
				$timestamp = (int) $matches[1];

				if (isset($matches[6])) {
					$type = 'SYSTEM';
					$value = $matches[6];
				} else {
					$type = $matches[4] ?? '';
					$value = $matches[5] ?? '';
				}

				$host = null;
				$service = null;

				if ($type === 'Warning') {
					if (preg_match("/^Host '[^']+' has no default contacts or contactgroups defined!$/", $value)) {
						//We don't support host-related stuff, so this is irrelevevant.
						continue;
					}
					if (preg_match("/^Service '[^']+' on host '[^']+' has no default contacts or contactgroups defined!$/", $value)) {
						//Not having contacts associated with a service is perfectly reasonable.
						continue;
					}
				}

				if ($type === 'SERVICE ALERT') {
					list($host, $service) = $this->getServiceAlertAssociations($value);
				} else if ($type === 'CURRENT SERVICE STATE') {
					list($host, $service) = $this->getCurrentServiceStateAssociations($value);
				} else if ($type === 'Warning') {
					list($host, $service) = $this->getWarningAssociations($value);
				} else if ($type === 'CURRENT HOST STATE') {
					$host = $this->getCurrentHostStateAssociations($value);
				} else if ($type === 'EXTERNAL COMMAND') {
					list($host, $service) = $this->getExternalCommandAssociations($value);
				} else if ($type === 'SERVICE NOTIFICATION') {
					list($host, $service) = $this->getServiceNotificationAssociations($value);
				} else if ($type === 'SERVICE FLAPPING ALERT') {
					list($host, $service) = $this->getServiceAlertAssociations($value);
				}

				$objects[] = new LogEntry($type, $timestamp, $value, $host, $service);
			} else {
				throw new ParseException(sprintf('Cannot parse line `%s`', $line));
			}
		}

		return array_reverse($objects);
	}

	/**
	 * @param string $value
	 * @return array{Host|null, Service|null}
	 */
	private function getServiceAlertAssociations($value) {
		list($hostName, $serviceName, $_rest) = explode(';', $value, 3);
		return $this->getServiceAssociationByNames($hostName, $serviceName);
	}

	/**
	 * @param string $value
	 * @return array{Host|null, Service|null}
	 */
	private function getCurrentServiceStateAssociations($value) {
		list($hostName, $serviceName, $_rest) = explode(';', $value, 3);
		return $this->getServiceAssociationByNames($hostName, $serviceName);
	}

	/**
	 * @param string $value
	 * @return array{Host|null, Service|null}
	 */
	private function getWarningAssociations($value) {
		if (preg_match("/^Service '([^']+)' on host '([^']+)'/", $value, $matches)) {
			return $this->getServiceAssociationByNames($matches[2], $matches[1]);
		}

		if (preg_match("/^Host '([^']+)'/", $value, $matches)) {
			return array($this->lookupHostByName($matches[1]), null);
		}

		return array(null, null);
	}

	/**
	 * @param string $value
	 * @return Host|null
	 */
	private function getCurrentHostStateAssociations($value) {
		list($hostName, $_rest) = explode(';', $value, 2);
		return $this->lookupHostByName($hostName);
	}

	/**
	 * @param string $value
	 * @return array{Host|null, Service|null}
	 */
	private function getExternalCommandAssociations($value) {
		if (preg_match("/^SCHEDULE_SVC_CHECK;([^;]+);([^;]+)/", $value, $matches)) {
			return $this->getServiceAssociationByNames($matches[1], $matches[2]);
		}
		return array(null, null);
	}

	/**
	 * @param string $value
	 * @return array{Host|null, Service|null}
	 */
	private function getServiceNotificationAssociations($value) {
		if (preg_match("/^(?:[^;]+);([^;]+);([^;]+)/", $value, $matches)) {
			return $this->getServiceAssociationByNames($matches[1], $matches[2]);
		}
		return array(null, null);
	}

	/**
	 * @param string $hostName
	 * @param string $serviceName
	 * @return array{Host|null, Service|null}
	 */
	private function getServiceAssociationByNames($hostName, $serviceName) {
		$this->loadServicesMap();

		$ident = $hostName . '/' . $serviceName;

		if (!isset($this->servicesMap[$ident])) {
			return array(null, null);
		}

		$service = $this->servicesMap[$ident];

		return array($service->getHost(), $service);
	}

	private function loadServicesMap(): void {
		if ($this->servicesMap !== null) {
			return;
		}

		$this->servicesMap = array();
		foreach ($this->serviceRepository->findAll() as $service) {
			$this->servicesMap[$service->getHost()->getName() . '/' . $service->getName()] = $service;
		}
	}

	/**
	 * @param string $hostName
	 * @return Host|NULL
	 */
	private function lookupHostByName($hostName) {
		$this->loadHostsMap();

		if (!isset($this->hostsMap[$hostName])) {
			return null;
		}

		return $this->hostsMap[$hostName];
	}

	private function loadHostsMap(): void {
		if ($this->hostsMap !== null) {
			return;
		}

		$this->hostsMap = array();
		foreach ($this->hostRepository->findAll() as $host) {
			$this->hostsMap[$host->getName()] = $host;
		}
	}

}

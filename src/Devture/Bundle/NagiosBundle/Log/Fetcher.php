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

	private $logFilePath;
	private $hostRepository;
	private $serviceRepository;
	private $hostsMap;
	private $servicesMap;

	public function __construct($logFilePath, HostRepository $hostRepository, ServiceRepository $serviceRepository) {
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
		return $this->parse(file_get_contents($this->logFilePath));
	}

	public function fetchForService(Service $service) {
		return array_filter($this->fetch(), function (LogEntry $logEntry) use ($service) {
			return ($logEntry->getService() === $service);
		});
	}

	private function parse($contents) {
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
					$type = $matches[4];
					$value = $matches[5];
				}

				$host = null;
				$service = null;

				if ($type === 'Warning') {
					if (preg_match("/^Host '[^']+' has no default contacts or contactgroups defined!$/", $value)) {
						//We don't support host-related stuff, so this is irrelevevant.
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

	private function getServiceAlertAssociations($value) {
		list($hostName, $serviceName, $_rest) = explode(';', $value, 3);
		return $this->getServiceAssociationByNames($hostName, $serviceName);
	}

	private function getCurrentServiceStateAssociations($value) {
		list($hostName, $serviceName, $_rest) = explode(';', $value, 3);
		return $this->getServiceAssociationByNames($hostName, $serviceName);
	}

	private function getWarningAssociations($value) {
		if (preg_match("/^Service '([^']+)' on host '([^']+)'/", $value, $matches)) {
			return $this->getServiceAssociationByNames($matches[2], $matches[1]);
		}

		if (preg_match("/^Host '([^']+)'/", $value, $matches)) {
			return array($this->lookupHostByName($matches[1]), null);
		}

		return array(null, null);
	}

	private function getCurrentHostStateAssociations($value) {
		list($hostName, $_rest) = explode(';', $value, 2);
		return $this->lookupHostByName($hostName);
	}

	private function getExternalCommandAssociations($value) {
		if (preg_match("/^SCHEDULE_SVC_CHECK;([^;]+);([^;]+)/", $value, $matches)) {
			return $this->getServiceAssociationByNames($matches[1], $matches[2]);
		}
		return array(null, null);
	}

	private function getServiceNotificationAssociations($value) {
		if (preg_match("/^(?:[^;]+);([^;]+);([^;]+)/", $value, $matches)) {
			return $this->getServiceAssociationByNames($matches[1], $matches[2]);
		}
		return array(null, null);
	}

	private function getServiceAssociationByNames($hostName, $serviceName) {
		$this->loadServicesMap();

		$ident = $hostName . '/' . $serviceName;

		if (!isset($this->servicesMap[$ident])) {
			return array(null, null);
		}

		/* @var $service Service */
		$service = $this->servicesMap[$ident];

		return array($service->getHost(), $service);
	}

	private function loadServicesMap() {
		if ($this->servicesMap !== null) {
			return;
		}

		/* @var $service Service */
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

	private function loadHostsMap() {
		if ($this->hostsMap !== null) {
			return;
		}

		/* @var $host Host */
		$this->hostsMap = array();
		foreach ($this->hostRepository->findAll() as $host) {
			$this->hostsMap[$host->getName()] = $host;
		}
	}

}

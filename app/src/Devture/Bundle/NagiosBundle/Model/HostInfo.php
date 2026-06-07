<?php
namespace Devture\Bundle\NagiosBundle\Model;

class HostInfo {

	private Host $host;

	/**
	 * @var list<ServiceInfo>
	 */
	private array $servicesInfo;

	/**
	 * @param list<ServiceInfo> $servicesInfo
	 */
	public function __construct(Host $host, array $servicesInfo) {
		$this->host = $host;
		$this->servicesInfo = $servicesInfo;
	}

	public function getHost(): Host {
		return $this->host;
	}

	/**
	 * @return list<ServiceInfo>
	 */
	public function getServicesInfo(): array {
		return $this->servicesInfo;
	}

}

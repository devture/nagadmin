<?php
namespace Devture\Bundle\NagiosBundle\ApiModelBridge;

use Devture\Bundle\NagiosBundle\Model\HostInfo;
use Devture\Bundle\NagiosBundle\Model\ServiceInfo;

class HostInfoBridge {

	private $hostBridge;
	private $serviceStatusBridge;

	public function __construct(HostBridge $hostBridge, ServiceInfoBridge $servicesInfoBridge) {
		$this->hostBridge = $hostBridge;
		$this->servicesInfoBridge = $servicesInfoBridge;
	}

	public function export(HostInfo $entity) {
		$servicesInfo = array_map(function (ServiceInfo $serviceInfo) {
			return $this->servicesInfoBridge->export($serviceInfo);
		}, $entity->getServicesInfo());

		$host = $entity->getHost();

		return array(
			'id' => (string) $host->getId(),
			'host' => $this->hostBridge->export($host),
			'servicesInfo' => $servicesInfo,
		);
	}

}
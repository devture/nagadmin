<?php
namespace Devture\Bundle\NagiosBundle\Controller\Api;

use Symfony\Component\HttpFoundation\Request;
use Devture\Bundle\SharedBundle\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Model\HostInfo;
use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Model\ServiceInfo;

class HostApiController extends \Devture\Bundle\NagiosBundle\Controller\BaseController {

	public function infoAction(Request $request, $id) {
		$selectedHost = null;
		try {
			if ($id) {
				$selectedHost = $this->getHostRepository()->find($id);
			}
		} catch (NotFound $e) {

		}

		$hosts = $this->getHostRepository()->findBy(array(), array('sort' => array('name' => 1)));

		$items = array();
		foreach ($hosts as $host) {
			if ($selectedHost === null || $selectedHost === $host) {
				$items[] = $this->createHostInfo($host);
			}
		}

		/** @var $hostInfoBridge \Devture\Bundle\NagiosBundle\ApiModelBridge\HostInfoBridge */
		$hostInfoBridge = $this->getNs('host_info.api_model_bridge');

		if ($selectedHost === null) {
			$result = array_map(function (HostInfo $entity) use ($hostInfoBridge) {
				return $hostInfoBridge->export($entity);
			}, $items);
		} else {
			$result = $hostInfoBridge->export($items[0]);
		}

		return $this->json($result);
	}

	public function recheckAllServicesAction(Request $request, $id, $token) {
		$intention = 'nagadmin';
		if ($this->isValidCsrfToken($intention, $token)) {
			try {
				$host = $this->getHostRepository()->find($id);
				$commandManager = $this->getNagiosCommandManager();

				foreach ($this->getServiceRepository()->findByHost($host) as $service) {
					$commandManager->scheduleServiceCheck($service);
				}
			} catch (NotFound $e) {

			}
			return $this->json(array('ok' => true));
		}
		return $this->json(array('ok' => false));
	}

	private function createHostInfo(Host $host) {
		$services = $this->getServiceRepository()->findByHost($host);

		$servicesInfo = array_map(function (Service $service) {
			$serviceStatus = $this->getStatusManager()->getServiceStatus($service);
			return new ServiceInfo($service, $serviceStatus);
		}, $services);

		return new HostInfo($host, $servicesInfo);
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Status\Manager
	 */
	private function getStatusManager() {
		return $this->getNs('status.manager');
	}

}
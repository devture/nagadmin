<?php
namespace Devture\Bundle\NagiosBundle\Controller\Api;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Model\HostInfo;
use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Model\ServiceInfo;
use Devture\Bundle\NagiosBundle\Status\ServiceStatus;

class HostApiController extends \Devture\Bundle\NagiosBundle\Controller\BaseController {

	public function infoAction(Request $request, $id) {
		$selectedHost = null;
		try {
			if ($id) {
				$selectedHost = $this->getHostRepository()->find($id);

				if (!$this->getAccessChecker()->canUserViewHost($this->getUser(), $selectedHost)) {
					return $this->abort(401);
				}
			}
		} catch (NotFound $e) {
			return $this->json(array());
		}

		$hosts = $this->getHostRepository()->findBy(array(), array('sort' => array('name' => 1)));

		$accessChecker = $this->getAccessChecker();

		$items = array();
		foreach ($hosts as $host) {
			if ($selectedHost === null || $selectedHost === $host) {
				if (!$accessChecker->canUserViewHost($this->getUser(), $host)) {
					continue;
				}
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

	public function recheckServicesAction(Request $request, $id, $recheckType, $token) {
		$intention = 'nagadmin';
		if ($this->isValidCsrfToken($intention, $token)) {
			$scheduledCount = 0;
			try {
				$host = $this->getHostRepository()->find($id);

				if (!$this->getAccessChecker()->canUserViewHost($this->getUser(), $host)) {
					return $this->json(array('ok' => false, 'unauthorized' => true));
				}

				$commandManager = $this->getNagiosCommandManager();

				foreach ($this->getServiceRepository()->findByHost($host) as $service) {
					if ($this->shouldRecheckService($service, $recheckType)) {
						$scheduledCount += 1;
						$commandManager->scheduleServiceCheck($service);
					}
				}
			} catch (NotFound $e) {

			}
			return $this->json(array('ok' => true, 'scheduledCount' => $scheduledCount));
		}
		return $this->json(array('ok' => false, 'unauthorized' => true));
	}

	private function createHostInfo(Host $host) {
		$services = $this->getServiceRepository()->findByHost($host);

		$statusManager = $this->getStatusManager();
		$servicesInfo = array_map(function (Service $service) use ($statusManager) {
			$serviceStatus = $statusManager->getServiceStatus($service);
			return new ServiceInfo($service, $serviceStatus);
		}, $services);

		return new HostInfo($host, $servicesInfo);
	}

	private function shouldRecheckService(Service $service, $recheckType) {
		if ($recheckType === 'all') {
			return true;
		}

		if ($recheckType === 'failing') {
			$status = $this->getStatusManager()->getServiceStatus($service);
			if ($status === null) {
				return false;
			}
			if (!$status->isChecked()) {
				return true;
			}
			return ($status->getCurrentState() !== ServiceStatus::STATUS_OK);
		}

		throw new \InvalidArgumentException('Unknown recheck type: ' . $recheckType);
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Status\Manager
	 */
	private function getStatusManager() {
		return $this->getNs('status.manager');
	}

}

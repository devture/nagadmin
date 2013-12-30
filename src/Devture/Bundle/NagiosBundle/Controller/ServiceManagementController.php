<?php
namespace Devture\Bundle\NagiosBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Model\Service;

class ServiceManagementController extends BaseController {

	public function indexAction(Request $request) {
		return $this->renderView('DevtureNagiosBundle/service/index.html.twig');
	}

	private function getBaseViewData() {
		$viewData = array();
		$viewData['contacts'] = $this->getContactRepository()->findBy(array(), array('sort' => array('name' => 1)));
		return $viewData;
	}

	public function addAction(Request $request, $hostId, $commandId) {
		/* @var $entity Service */
		$entity = $this->getServiceRepository()->createModel(array());

		$defaults = $this->getNs('service.defaults');

		$entity->setMaxCheckAttempts($defaults['max_check_attempts']);
		$entity->setCheckInterval($defaults['check_interval']);
		$entity->setRetryInterval($defaults['retry_interval']);
		$entity->setNotificationInterval($defaults['notification_interval']);

		try {
			/* @var $command Command*/
			$command = $this->getCommandRepository()->find($commandId);

			if ($command->getType() !== Command::TYPE_SERVICE_CHECK) {
				throw new NotFound('Only service check commands are allowed.');
			}

			$entity->setCommand($command);
			$entity->setName($command->getTitle());
		} catch (NotFound $e) {
			return $this->abort(404);
		}

		try {
			$host = $this->getHostRepository()->find($hostId);

			if (!$this->getAccessChecker()->canUserManageHost($this->getUser(), $host)) {
				return $this->abort(401);
			}

			$entity->setHost($host);
		} catch (NotFound $e) {
			return $this->abort(404);
		}

		$binder = $this->getServiceFormBinder();
		if ($request->getMethod() === 'POST' && $binder->bind($entity, $request)) {
			$this->getServiceRepository()->add($entity);
			$this->tryDeployConfiguration();
			$next = $request->query->has('next') ? $request->query->get('next') : $this->generateUrlNs('service.manage');
			return $this->redirect($next);
		}

		return $this->renderView('DevtureNagiosBundle/service/record.html.twig', array_merge($this->getBaseViewData(), array(
			'entity' => $entity,
			'isAdded' => false,
			'form' => $binder,
		)));
	}

	public function editAction(Request $request, $id) {
		try {
			/* @var $entity Service */
			$entity = $this->getServiceRepository()->find($id);
		} catch (NotFound $e) {
			return $this->abort(404);
		}

		if (!$this->getAccessChecker()->canUserManageService($this->getUser(), $entity)) {
			return $this->abort(401);
		}

		$binder = $this->getServiceFormBinder();
		if ($request->getMethod() === 'POST' && $binder->bind($entity, $request)) {
			$this->getServiceRepository()->update($entity);
			$this->tryDeployConfiguration();
			$next = $request->query->has('next') ? $request->query->get('next') : $this->generateUrlNs('service.manage');
			return $this->redirect($next);
		}

		return $this->renderView('DevtureNagiosBundle/service/record.html.twig', array_merge($this->getBaseViewData(), array(
			'entity' => $entity,
			'isAdded' => true,
			'form' => $binder,
		)));
	}

	public function viewAction(Request $request, $id) {
		try {
			/* @var $entity Service */
			$entity = $this->getServiceRepository()->find($id);
		} catch (NotFound $e) {
			return $this->abort(404);
		}

		if (!$this->getAccessChecker()->canUserViewService($this->getUser(), $entity)) {
			return $this->json(array('ok' => false));
		}

		return $this->renderView('DevtureNagiosBundle/service/view.html.twig', array_merge($this->getBaseViewData(), array(
			'entity' => $entity,
			'logs' => $this->getLogFetcher()->fetchForService($entity),
		)));
	}

	public function scheduleCheckAction(Request $request, $id, $token) {
		$intention = 'schedule-service-check-' . $id;
		if ($this->isValidCsrfToken($intention, $token)) {
			try {
				/* @var $service Service */
				$service = $this->getServiceRepository()->find($id);

				if (!$this->getAccessChecker()->canUserManageService($this->getUser(), $service)) {
					return $this->json(array('ok' => false));
				}

				$this->getNagiosCommandManager()->scheduleServiceCheck($service);
			} catch (NotFound $e) {

			}
			return $this->json(array('ok' => true));
		}
		return $this->json(array('ok' => false));
	}

	public function deleteAction(Request $request, $id, $token) {
		$intention = 'delete-service-' . $id;
		if ($this->isValidCsrfToken($intention, $token)) {
			try {
				/* @var $service Service */
				$service = $this->getServiceRepository()->find($id);

				if (!$this->getAccessChecker()->canUserManageService($this->getUser(), $service)) {
					return $this->json(array('ok' => false));
				}

				$this->getServiceRepository()->delete($service);
				$this->tryDeployConfiguration();
			} catch (NotFound $e) {

			}
			return $this->json(array('ok' => true));
		}
		return $this->json(array('ok' => false));
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Form\ServiceFormBinder
	 */
	private function getServiceFormBinder() {
		return $this->getNs('service.form_binder');
	}

}

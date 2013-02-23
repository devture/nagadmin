<?php
namespace Devture\Bundle\NagiosBundle\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Devture\Bundle\SharedBundle\Exception\NotFound;
use Devture\Bundle\SharedBundle\Controller\BaseController;
use Devture\Bundle\NagiosBundle\Model\Command;

class ServiceManagementController extends BaseController {

	public function indexAction(Request $request) {
		$selectedHost = null;
		try {
			$hostId = $request->query->get('hostId');
			if ($hostId) {
				$selectedHost = $this->getNs('host.repository')->find($hostId);
			}
		} catch (NotFound $e) {

		}

		$hosts = $this->getNs('host.repository')->findBy(array(), array(
				'sort' => array('name' => 1)));

		$items = array();
		foreach ($hosts as $host) {
			if ($selectedHost === null || $selectedHost === $host) {
				foreach ($this->getNs('service.repository')->findByHost($host) as $service) {
					$items[] = $service;
				}
			}
		}


		$findBy = array('type' => Command::TYPE_SERVICE_CHECK);
		$commands = $this->getNs('command.repository')->findBy($findBy, array(
				'sort' => array('title' => 1)));

		return $this->renderView('DevtureNagiosBundle/service/index.html.twig', array(
			'items' => $items,
			'commands' => $commands,
			'hosts' => $hosts,
			'selectedHost' => $selectedHost,
		));
	}

	private function getBaseViewData() {
		$viewData = array();
		$viewData['hosts'] = $this->getNs('host.repository')->findBy(array(), array(
				'sort' => array('name' => 1)));
		$viewData['contacts'] = $this->getNs('contact.repository')->findBy(array(), array(
				'sort' => array('name' => 1)));
		return $viewData;
	}

	public function addAction(Request $request, $commandId) {
		$entity = $this->getNs('service.repository')->createModel(array());

		$defaults = $this->getNs('service.defaults');

		$entity->setMaxCheckAttempts($defaults['max_check_attempts']);
		$entity->setCheckInterval($defaults['check_interval']);
		$entity->setRetryInterval($defaults['retry_interval']);
		$entity->setNotificationInterval($defaults['notification_interval']);

		try {
			$command = $this->getNs('command.repository')->find($commandId);
			$entity->setCommand($command);
			$entity->setName($command->getTitle());
		} catch (NotFound $e) {
			return $this->abort(404);
		}

		if ($request->query->has('hostId')) {
			try {
				$entity->setHost($this->getNs('host.repository')->find($request->query->get('hostId')));
			} catch (NotFound $e) {
				return $this->abort(404);
			}
		}

		$binder = $this->getNs('service.form_binder');
		if ($request->getMethod() === 'POST'
				&& $binder->bindProtectedRequest($entity, $request)) {
			$this->getNs('service.repository')->add($entity);
			$next = $request->query->has('next') ? $request->query->get('next') : $this->generateUrlNs('service.manage');
			return $this->redirect($next);
		}

		return $this->renderView('DevtureNagiosBundle/service/record.html.twig', array_merge($this->getBaseViewData(), array(
				'entity' => $entity,
				'isAdded' => false,
				'form' => $binder,)));
	}

	public function editAction(Request $request, $id) {
		try {
			$entity = $this->getNs('service.repository')->find($id);
		} catch (NotFound $e) {
			return $this->abort(404);
		}

		$binder = $this->getNs('service.form_binder');
		if ($request->getMethod() === 'POST'
				&& $binder->bindProtectedRequest($entity, $request)) {
			$this->getNs('service.repository')->update($entity);
			$next = $request->query->has('next') ? $request->query->get('next') : $this->generateUrlNs('service.manage');
			return $this->redirect($next);
		}

		return $this->renderView('DevtureNagiosBundle/service/record.html.twig', array_merge($this->getBaseViewData(), array(
				'entity' => $entity,
				'isAdded' => true,
				'form' => $binder,)));
	}

	public function deleteAction(Request $request, $id, $token) {
		$intention = 'delete-service-' . $id;
		if ($this->get('shared.csrf_token_generator')->isValid($intention, $token)) {
			try {
				$this->getNs('service.repository')->delete($this->getNs('service.repository')->find($id));
			} catch (NotFound $e) {

			}
			return $this->json(array('ok' => true));
		}
		return $this->json(array());
	}

}
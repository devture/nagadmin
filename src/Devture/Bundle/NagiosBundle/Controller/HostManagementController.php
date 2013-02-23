<?php
namespace Devture\Bundle\NagiosBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Devture\Bundle\SharedBundle\Exception\NotFound;
use Devture\Bundle\SharedBundle\Controller\BaseController;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Model\Command;

class HostManagementController extends BaseController {

	public function indexAction() {
		$items = $this->getNs('host.repository')->findBy(array(), array('sort' => array('name' => 1)));
		return $this->renderView('DevtureNagiosBundle/host/index.html.twig', array('items' => $items));
	}

	private function getBaseViewData(Host $currentHost) {
		$groupsMap = array();
		$hosts = $this->getNs('host.repository')->findBy(array(), array());
		$hosts[] = $currentHost;
		foreach ($hosts as $host) {
			foreach ($host->getGroups() as $groupName) {
				$groupsMap[$groupName] = true;
			}
		}
		ksort($groupsMap);

		$findBy = array('type' => Command::TYPE_SERVICE_CHECK);
		$commands = $this->getNs('command.repository')->findBy($findBy, array('sort' => array('title' => 1)));

		$viewData = array();
		$viewData['groups'] = array_keys($groupsMap);
		$viewData['services'] = $this->getNs('service.repository')->findByHost($currentHost);
		$viewData['commands'] = $commands;
		return $viewData;
	}

	public function addAction(Request $request) {
		$entity = $this->getNs('host.repository')->createModel(array());

		$binder = $this->getNs('host.form_binder');
		if ($request->getMethod() === 'POST' && $binder->bindProtectedRequest($entity, $request)) {
			$this->getNs('host.repository')->add($entity);
			return $this->redirect($this->generateUrlNs('host.manage'));
		}

		return $this->renderView('DevtureNagiosBundle/host/record.html.twig', array_merge($this->getBaseViewData($entity), array(
			'entity' => $entity,
			'isAdded' => false,
			'form' => $binder,)
		));
	}

	public function editAction(Request $request, $id) {
		try {
			$entity = $this->getNs('host.repository')->find($id);
		} catch (NotFound $e) {
			return $this->abort(404);
		}

		$binder = $this->getNs('host.form_binder');
		if ($request->getMethod() === 'POST' && $binder->bindProtectedRequest($entity, $request)) {
			$this->getNs('host.repository')->update($entity);
			return $this->redirect($this->generateUrlNs('host.manage'));
		}

		return $this->renderView('DevtureNagiosBundle/host/record.html.twig', array_merge($this->getBaseViewData($entity), array(
			'entity' => $entity,
			'isAdded' => true,
			'form' => $binder,
		)));
	}

	public function deleteAction(Request $request, $id, $token) {
		$intention = 'delete-host-' . $id;
		if ($this->get('shared.csrf_token_generator')->isValid($intention, $token)) {
			try {
				$this->getNs('host.repository')->delete($this->getNs('host.repository')->find($id));
			} catch (NotFound $e) {

			}
			return $this->json(array('ok' => true));
		}
		return $this->json(array());
	}

}
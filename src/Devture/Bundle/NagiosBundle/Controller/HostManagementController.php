<?php
namespace Devture\Bundle\NagiosBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Model\Command;

class HostManagementController extends BaseController {

	public function indexAction() {
		if (!$this->getAccessChecker()->canUserManageHosts($this->getUser())) {
			return $this->abort(401);
		}
		$items = $this->getHostRepository()->findBy(array(), array('sort' => array('name' => 1)));
		return $this->renderView('DevtureNagiosBundle/host/index.html.twig', array('items' => $items));
	}

	private function getBaseViewData(Host $currentHost) {
		$groups = array_unique(array_merge($this->getHostRepository()->getDistinctGroups(), $currentHost->getGroups()));

		$findBy = array('type' => Command::TYPE_SERVICE_CHECK);
		$commands = $this->getCommandRepository()->findBy($findBy, array('sort' => array('title' => 1)));

		$viewData = array();
		$viewData['groups'] = $groups;
		$viewData['services'] = $this->getServiceRepository()->findByHost($currentHost);
		$viewData['commands'] = $commands;
		return $viewData;
	}

	public function addAction(Request $request) {
		if (!$this->getAccessChecker()->canUserCreateHosts($this->getUser())) {
			return $this->abort(401);
		}

		/* @var $entity Host */
		$entity = $this->getHostRepository()->createModel(array());

		$binder = $this->getHostFormBinder();
		if ($request->getMethod() === 'POST' && $binder->bind($entity, $request)) {
			$this->getHostRepository()->add($entity);
			return $this->redirect($this->generateUrlNs('host.manage'));
		}

		return $this->renderView('DevtureNagiosBundle/host/record.html.twig', array_merge($this->getBaseViewData($entity), array(
			'entity' => $entity,
			'isAdded' => false,
			'form' => $binder,
		)));
	}

	public function editAction(Request $request, $id) {
		try {
			/* @var $entity Host */
			$entity = $this->getHostRepository()->find($id);
		} catch (NotFound $e) {
			return $this->abort(404);
		}

		if (!$this->getAccessChecker()->canUserManageHost($this->getUser(), $entity)) {
			return $this->abort(401);
		}

		$binder = $this->getHostFormBinder();
		if ($request->getMethod() === 'POST' && $binder->bind($entity, $request)) {
			$this->getHostRepository()->update($entity);
			$this->tryDeployConfiguration();
			return $this->redirect($this->generateUrlNs('host.manage'));
		}

		return $this->renderView('DevtureNagiosBundle/host/record.html.twig', array_merge($this->getBaseViewData($entity), array(
			'entity' => $entity,
			'isAdded' => true,
			'form' => $binder,
		)));
	}

	public function viewAction(Request $request, $id) {
		try {
			/* @var $entity Host */
			$entity = $this->getHostRepository()->find($id);
		} catch (NotFound $e) {
			return $this->abort(404);
		}

		if (!$this->getAccessChecker()->canUserViewHost($this->getUser(), $entity)) {
			return $this->abort(403);
		}

		return $this->renderView('DevtureNagiosBundle/host/view.html.twig', array(
			'entity' => $entity,
			'logs' => $this->getLogFetcher()->fetchForHost($entity),
		));
	}

	public function deleteAction(Request $request, $id, $token) {
		$intention = 'delete-host-' . $id;
		if ($this->isValidCsrfToken($intention, $token)) {
			try {
				/* @var $host Host */
				$host = $this->getHostRepository()->find($id);

				if (!$this->getAccessChecker()->canUserManageHost($this->getUser(), $host)) {
					return $this->json(array('ok' => false));
				}

				$this->getHostRepository()->delete($host);
				$this->tryDeployConfiguration();
			} catch (NotFound $e) {

			}
			return $this->json(array('ok' => true));
		}
		return $this->json(array('ok' => false));
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Form\HostFormBinder
	 */
	private function getHostFormBinder() {
		return $this->getNs('host.form_binder');
	}

}

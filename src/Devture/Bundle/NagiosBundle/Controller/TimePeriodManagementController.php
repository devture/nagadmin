<?php
namespace Devture\Bundle\NagiosBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Devture\Bundle\SharedBundle\Exception\NotFound;
use Devture\Bundle\SharedBundle\Controller\BaseController;

class TimePeriodManagementController extends BaseController {

	public function indexAction() {
		$items = $this->getNs('time_period.repository')->findBy(array(), array(
				'sort' => array('title' => 1)));
		return $this->renderView('DevtureNagiosBundle/time_period/index.html.twig', array('items' => $items));
	}

	public function addAction(Request $request) {
		$entity = $this->getNs('time_period.repository')->createModel(array());

		$binder = $this->getNs('time_period.form_binder');
		if ($request->getMethod() === 'POST'
				&& $binder->bindProtectedRequest($entity, $request)) {
			$this->getNs('time_period.repository')->add($entity);
			return $this->redirect($this->generateUrlNs('time_period.manage'));
		}

		return $this->renderView('DevtureNagiosBundle/time_period/record.html.twig', array(
				'entity' => $entity,
				'isAdded' => false,
				'form' => $binder,));
	}

	public function editAction(Request $request, $id) {
		try {
			$entity = $this->getNs('time_period.repository')->find($id);
		} catch (NotFound $e) {
			return $this->abort(404);
		}

		$binder = $this->getNs('time_period.form_binder');
		if ($request->getMethod() === 'POST'
				&& $binder->bindProtectedRequest($entity, $request)) {
			$this->getNs('time_period.repository')->update($entity);
			return $this->redirect($this->generateUrlNs('time_period.manage'));
		}

		return $this->renderView('DevtureNagiosBundle/time_period/record.html.twig', array(
				'entity' => $entity,
				'isAdded' => true,
				'form' => $binder,));
	}

	public function deleteAction(Request $request, $id, $token) {
		$intention = 'delete-time-period-' . $id;
		if ($this->get('shared.csrf_token_generator')->isValid($intention, $token)) {
			try {
				$this->getNs('time_period.repository')->delete($this->getNs('time_period.repository')->find($id));
			} catch (NotFound $e) {

			}
			return $this->json(array('ok' => true));
		}
		return $this->json(array());
	}

}
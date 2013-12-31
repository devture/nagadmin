<?php
namespace Devture\Bundle\NagiosBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Model\TimePeriod;

class TimePeriodManagementController extends BaseController {

	public function indexAction() {
		$items = $this->getTimePeriodRepository()->findBy(array(), array('sort' => array('title' => 1)));
		return $this->renderView('DevtureNagiosBundle/time_period/index.html.twig', array('items' => $items));
	}

	public function addAction(Request $request) {
		$entity = $this->getTimePeriodRepository()->createModel(array());

		$binder = $this->getTimePeriodFormBinder();
		if ($request->getMethod() === 'POST' && $binder->bind($entity, $request)) {
			$this->getTimePeriodRepository()->add($entity);
			return $this->redirect($this->generateUrlNs('time_period.manage'));
		}

		return $this->renderView('DevtureNagiosBundle/time_period/record.html.twig', array(
			'entity' => $entity,
			'isAdded' => false,
			'isUsed' => false,
			'form' => $binder,
		));
	}

	public function editAction(Request $request, $id) {
		try {
			$entity = $this->getTimePeriodRepository()->find($id);
		} catch (NotFound $e) {
			return $this->abort(404);
		}

		$binder = $this->getTimePeriodFormBinder();
		if ($request->getMethod() === 'POST' && $binder->bind($entity, $request)) {
			$this->getTimePeriodRepository()->update($entity);
			$this->tryDeployConfiguration();
			return $this->redirect($this->generateUrlNs('time_period.manage'));
		}

		return $this->renderView('DevtureNagiosBundle/time_period/record.html.twig', array(
			'entity' => $entity,
			'isAdded' => true,
			'isUsed' => $this->isTimePeriodUsed($entity),
			'form' => $binder,
		));
	}

	public function deleteAction(Request $request, $id, $token) {
		$intention = 'delete-time-period-' . $id;
		if ($this->isValidCsrfToken($intention, $token)) {
			try {
				$timePeriod = $this->getTimePeriodRepository()->find($id);
				if ($this->isTimePeriodUsed($timePeriod)) {
					return $this->json(array('ok' => false));
				}

				$this->getTimePeriodRepository()->delete($timePeriod);
				$this->tryDeployConfiguration();
			} catch (NotFound $e) {

			}
			return $this->json(array('ok' => true));
		}
		return $this->json(array('ok' => false));
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Form\TimePeriodFormBinder
	 */
	private function getTimePeriodFormBinder() {
		return $this->getNs('time_period.form_binder');
	}

	private function isTimePeriodUsed(TimePeriod $entity) {
		return (count($this->getContactRepository()->findByTimePeriod($entity)) !== 0);
	}

}

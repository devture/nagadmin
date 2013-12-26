<?php
namespace Devture\Bundle\NagiosBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Model\Contact;

class ContactManagementController extends BaseController {

	public function indexAction() {
		$items = $this->getContactRepository()->findBy(array(), array('sort' => array('name' => 1)));
		return $this->renderView('DevtureNagiosBundle/contact/index.html.twig', array('items' => $items));
	}

	private function getBaseViewData() {
		$viewData = array();
		$viewData['timePeriods'] = $this->getTimePeriodRepository()->findBy(array(), array(
			'sort' => array('title' => 1)
		));
		$viewData['notificationCommands'] = $this->getCommandRepository()->findBy(array('type' => Command::TYPE_SERVICE_NOTIFICATION), array(
			'sort' => array('title' => 1)
		));
		$viewData['addressSlotsCount'] = Contact::ADDRESS_SLOTS_COUNT;
		return $viewData;
	}

	public function addAction(Request $request) {
		$entity = $this->getContactRepository()->createModel(array());

		$binder = $this->getContactFormBinder();
		if ($request->getMethod() === 'POST' && $binder->bindProtectedRequest($entity, $request)) {
			$this->getContactRepository()->add($entity);
			return $this->redirect($this->generateUrlNs('contact.manage'));
		}

		return $this->renderView('DevtureNagiosBundle/contact/record.html.twig', array_merge($this->getBaseViewData(), array(
			'entity' => $entity,
			'isAdded' => false,
			'form' => $binder,
		)));
	}

	public function editAction(Request $request, $id) {
		try {
			$entity = $this->getContactRepository()->find($id);
		} catch (NotFound $e) {
			return $this->abort(404);
		}

		$binder = $this->getContactFormBinder();
		if ($request->getMethod() === 'POST' && $binder->bindProtectedRequest($entity, $request)) {
			$this->getContactRepository()->update($entity);
			$this->tryDeployConfiguration();
			return $this->redirect($this->generateUrlNs('contact.manage'));
		}

		return $this->renderView('DevtureNagiosBundle/contact/record.html.twig', array_merge($this->getBaseViewData(), array(
			'entity' => $entity,
			'isAdded' => true,
			'form' => $binder,
		)));
	}

	public function deleteAction(Request $request, $id, $token) {
		$intention = 'delete-contact-' . $id;
		if ($this->isValidCsrfToken($intention, $token)) {
			try {
				$this->getContactRepository()->delete($this->getContactRepository()->find($id));
				$this->tryDeployConfiguration();
			} catch (NotFound $e) {

			}
			return $this->json(array('ok' => true));
		}
		return $this->json(array('ok' => false));
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Form\ContactFormBinder
	 */
	private function getContactFormBinder() {
		return $this->getNs('contact.form_binder');
	}

}

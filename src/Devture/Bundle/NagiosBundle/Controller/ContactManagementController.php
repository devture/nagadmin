<?php
namespace Devture\Bundle\NagiosBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Devture\Bundle\SharedBundle\Exception\NotFound;
use Devture\Bundle\SharedBundle\Controller\BaseController;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Model\Contact;

class ContactManagementController extends BaseController {

	public function indexAction() {
		$items = $this->getNs('contact.repository')->findBy(array(), array('sort' => array('name' => 1)));
		return $this->renderView('DevtureNagiosBundle/contact/index.html.twig', array('items' => $items));
	}

	private function getBaseViewData() {
		$viewData = array();
		$viewData['timePeriods'] = $this->getNs('time_period.repository')->findBy(array(), array(
			'sort' => array('title' => 1)
		));
		$viewData['notificationCommands'] = $this->getNs('command.repository')->findBy(array('type' => Command::TYPE_SERVICE_NOTIFICATION), array(
			'sort' => array('title' => 1)
		));
		$viewData['addressSlotsCount'] = Contact::ADDRESS_SLOTS_COUNT;
		return $viewData;
	}

	public function addAction(Request $request) {
		$entity = $this->getNs('contact.repository')->createModel(array());

		$binder = $this->getNs('contact.form_binder');
		if ($request->getMethod() === 'POST' && $binder->bindProtectedRequest($entity, $request)) {
			$this->getNs('contact.repository')->add($entity);
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
			$entity = $this->getNs('contact.repository')->find($id);
		} catch (NotFound $e) {
			return $this->abort(404);
		}

		$binder = $this->getNs('contact.form_binder');
		if ($request->getMethod() === 'POST' && $binder->bindProtectedRequest($entity, $request)) {
			$this->getNs('contact.repository')->update($entity);
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
		if ($this->get('shared.csrf_token_generator')->isValid($intention, $token)) {
			try {
				$this->getNs('contact.repository')->delete($this->getNs('contact.repository')->find($id));
			} catch (NotFound $e) {

			}
			return $this->json(array('ok' => true));
		}
		return $this->json(array('ok' => false));
	}

}
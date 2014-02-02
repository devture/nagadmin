<?php
namespace Devture\Bundle\NagiosBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Model\Contact;

class ContactManagementController extends BaseController {

	public function indexAction() {
		if ($this->getAccessChecker()->canUserDoConfigurationManagement($this->getUser())) {
			$criteria = array();
		} else {
			$criteria = array('userId' => $this->getUser()->getId());
		}
		$items = $this->getContactRepository()->findBy($criteria, array('sort' => array('name' => 1)));
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
		$viewData['users'] = $this->getUserRepository()->findAll();
		return $viewData;
	}

	public function addAction(Request $request) {
		if (!$this->getAccessChecker()->canUserCreateContacts($this->getUser())) {
			return $this->abort(401);
		}

		$entity = $this->getContactRepository()->createModel(array());

		if (!$this->getAccessChecker()->canUserDoConfigurationManagement($this->getUser())) {
			$entity->setUser($this->getUser());
		}

		$binder = $this->getContactFormBinder();
		if ($request->getMethod() === 'POST' && $binder->bind($entity, $request)) {
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

		if (!$this->getAccessChecker()->canUserManageContact($this->getUser(), $entity)) {
			return $this->abort(401);
		}

		$binder = $this->getContactFormBinder();
		if ($request->getMethod() === 'POST' && $binder->bind($entity, $request)) {
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
				$contact = $this->getContactRepository()->find($id);

				if (!$this->getAccessChecker()->canUserManageContact($this->getUser(), $contact)) {
					return $this->json(array('ok' => false));
				}

				$this->getContactRepository()->delete($contact);
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

	/**
	 * @return \Devture\Bundle\UserBundle\Repository\UserRepositoryInterface
	 */
	private function getUserRepository() {
		return $this->get('devture_user.repository');
	}

}

<?php
namespace Devture\Bundle\NagiosBundle\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Devture\Bundle\SharedBundle\Exception\NotFound;
use Devture\Bundle\SharedBundle\Controller\BaseController;
use Devture\Bundle\NagiosBundle\Model\Command;

class CommandManagementController extends BaseController {

	public function indexAction($type) {
		if (!in_array($type, Command::getTypes())) {
			return $this->abort(404);
		}
		$findBy = array('type' => $type);
		$items = $this->getNs('command.repository')->findBy($findBy, array(
				'sort' => array('name' => 1)));
		return $this->renderView('command.index', array('items' => $items, 'type' => $type));
	}

	public function addAction(Request $request, $type) {
		$entity = $this->getNs('command.repository')->createModel(array());
		$entity->setType($type);

		$binder = $this->getNs('command.form_binder');
		if ($request->getMethod() === 'POST'
				&& $binder->bindProtectedRequest($entity, $request)) {
			$this->getNs('command.repository')->add($entity);
			return $this->redirect($this->generateUrlNs('command.manage', array('type' => $entity->getType())));
		}

		return $this->renderView('command.record', array(
				'entity' => $entity,
				'isAdded' => false,
				'form' => $binder,));
	}

	public function editAction(Request $request, $id) {
		try {
			$entity = $this->getNs('command.repository')->find($id);
		} catch (NotFound $e) {
			return $this->abort(404);
		}

		$binder = $this->getNs('command.form_binder');
		if ($request->getMethod() === 'POST'
				&& $binder->bindProtectedRequest($entity, $request)) {
			$this->getNs('command.repository')->update($entity);
			return $this->redirect($this->generateUrlNs('command.manage', array('type' => $entity->getType())));
		}

		return $this->renderView('command.record', array(
				'entity' => $entity,
				'isAdded' => true,
				'form' => $binder,));
	}

	public function deleteAction(Request $request, $id, $token) {
		$intention = 'delete-command-' . $id;
		if ($this->get('shared.csrf_token_generator')->isValid($intention, $token)) {
			try {
				$this->getNs('command.repository')->delete($this->getNs('command.repository')->find($id));
			} catch (NotFound $e) {

			}
			return $this->json(array('ok' => true));
		}
		return $this->json(array());
	}

}
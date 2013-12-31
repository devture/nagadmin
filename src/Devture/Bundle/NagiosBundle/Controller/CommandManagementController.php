<?php
namespace Devture\Bundle\NagiosBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Model\Command;

class CommandManagementController extends BaseController {

	public function indexAction($type) {
		if (!in_array($type, Command::getTypes())) {
			return $this->abort(404);
		}
		$findBy = array('type' => $type);
		$items = $this->getCommandRepository()->findBy($findBy, array('sort' => array('name' => 1)));
		return $this->renderView('DevtureNagiosBundle/command/index.html.twig', array('items' => $items, 'type' => $type));
	}

	public function addAction(Request $request, $type) {
		$entity = $this->getCommandRepository()->createModel(array());
		$entity->setType($type);

		$binder = $this->getCommandFormBinder();
		if ($request->getMethod() === 'POST' && $binder->bind($entity, $request)) {
			$this->getCommandRepository()->add($entity);
			return $this->redirect($this->generateUrlNs('command.manage', array('type' => $entity->getType())));
		}

		return $this->renderView('DevtureNagiosBundle/command/record.html.twig', array(
			'entity' => $entity,
			'isAdded' => false,
			'isUsed' => false,
			'form' => $binder,
		));
	}

	public function editAction(Request $request, $id) {
		try {
			$entity = $this->getCommandRepository()->find($id);
		} catch (NotFound $e) {
			return $this->abort(404);
		}

		$binder = $this->getCommandFormBinder();
		if ($request->getMethod() === 'POST' && $binder->bind($entity, $request)) {
			$this->getCommandRepository()->update($entity);
			$this->tryDeployConfiguration();
			return $this->redirect($this->generateUrlNs('command.manage', array('type' => $entity->getType())));
		}

		return $this->renderView('DevtureNagiosBundle/command/record.html.twig', array(
			'entity' => $entity,
			'isAdded' => true,
			'isUsed' => $this->isCommandUsed($entity),
			'form' => $binder,
		));
	}

	public function deleteAction(Request $request, $id, $token) {
		$intention = 'delete-command-' . $id;
		if ($this->isValidCsrfToken($intention, $token)) {
			try {
				$command = $this->getCommandRepository()->find($id);

				if ($this->isCommandUsed($command)) {
					return $this->json(array('ok' => false));
				}

				$this->getCommandRepository()->delete($command);
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
	private function getCommandFormBinder() {
		return $this->getNs('command.form_binder');
	}

	private function isCommandUsed(Command $entity) {
		if ($entity->getType() === Command::TYPE_SERVICE_CHECK) {
			return (count($this->getServiceRepository()->findByCommand($entity)) !== 0);
		}

		if ($entity->getType() === Command::TYPE_SERVICE_NOTIFICATION) {
			return (count($this->getContactRepository()->findByCommand($entity)) !== 0);
		}

		throw new \LogicException('Unknown command type: ' . $entity->getType());
	}

}

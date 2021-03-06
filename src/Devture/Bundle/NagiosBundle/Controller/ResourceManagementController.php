<?php
namespace Devture\Bundle\NagiosBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Devture\Bundle\NagiosBundle\Model\Resource;

class ResourceManagementController extends BaseController {

	public function manageAction(Request $request) {
		$entity = $this->getResourceRepository()->getResource();

		$binder = $this->getResourceFormBinder();
		if ($request->getMethod() === 'POST' && $binder->bind($entity, $request)) {
			$this->getResourceRepository()->update($entity);
			$this->tryDeployConfiguration();
			return $this->redirect($this->generateUrlNs('resource.manage'));
		}

		return $this->renderView('DevtureNagiosBundle/resource/management.html.twig', array(
			'userVariablesCount' => Resource::USER_VARIABLES_COUNT,
			'entity' => $entity,
			'form' => $binder,
		));
	}

	/**
	 * @return \Devture\Bundle\NagiosBundle\Form\ResourceFormBinder
	 */
	private function getResourceFormBinder() {
		return $this->getNs('resource.form_binder');
	}

}

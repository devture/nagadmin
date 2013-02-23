<?php
namespace Devture\Bundle\NagiosBundle\Controller;
use Symfony\Component\HttpFoundation\Request;
use Devture\Bundle\SharedBundle\Controller\BaseController;
use Devture\Bundle\NagiosBundle\Model\Resource;

class ResourceManagementController extends BaseController {

	public function manageAction(Request $request) {
		$entity = $this->getNs('resource.repository')->getResource();

		$binder = $this->getNs('resource.form_binder');
		if ($request->getMethod() === 'POST'
				&& $binder->bindProtectedRequest($entity, $request)) {
			$this->getNs('resource.repository')->update($entity);
			return $this->redirect($this->generateUrlNs('resource.manage'));
		}

		return $this->renderView('DevtureNagiosBundle/resource/management.html.twig', array(
				'userVariablesCount' => Resource::USER_VARIABLES_COUNT,
				'entity' => $entity,
				'form' => $binder,));
	}

}
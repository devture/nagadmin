<?php
namespace Devture\Bundle\NagiosBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class LogManagementController extends BaseController {

	public function manageAction(Request $request) {
		return $this->renderView('DevtureNagiosBundle/log/index.html.twig', array(
			'items' => $this->getLogFetcher()->fetch(),
		));
	}

}
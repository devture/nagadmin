<?php
namespace Devture\Bundle\NagiosBundle\Controller;

class LogManagementController extends BaseController {

	public function manageAction() {
		return $this->renderView('DevtureNagiosBundle/log/index.html.twig');
	}

}
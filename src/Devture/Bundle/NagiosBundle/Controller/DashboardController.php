<?php
namespace Devture\Bundle\NagiosBundle\Controller;

class DashboardController extends BaseController {

	public function dashboardAction() {
		return $this->renderView('DevtureNagiosBundle/dashboard/dashboard.html.twig');
	}

}
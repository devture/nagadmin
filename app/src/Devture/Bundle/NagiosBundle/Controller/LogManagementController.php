<?php

namespace Devture\Bundle\NagiosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/log')]
class LogManagementController extends AbstractController
{
	/**
	 * Renders the page shell; the live log table is a React component
	 * fed by the log API endpoint.
	 */
	#[Route('/manage', name: 'devture_nagios.log.manage', methods: ['GET'])]
	public function manage(): Response
	{
		return $this->render('@DevtureNagios/log/index.html.twig');
	}
}

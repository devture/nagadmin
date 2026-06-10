<?php

namespace Devture\Bundle\NagiosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
	#[Route('/', name: 'homepage', methods: ['GET'])]
	public function homepage(): RedirectResponse
	{
		return $this->redirectToRoute('devture_nagios.dashboard');
	}

	#[Route('/dashboard', name: 'devture_nagios.dashboard', methods: ['GET'])]
	public function dashboard(): Response
	{
		return $this->render('@DevtureNagios/dashboard/dashboard.html.twig');
	}
}

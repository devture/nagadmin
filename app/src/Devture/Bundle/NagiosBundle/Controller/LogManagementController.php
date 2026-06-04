<?php

namespace Devture\Bundle\NagiosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/log')]
class LogManagementController extends AbstractController
{
    /**
     * The log overview is an Angular live table (fed by the log API endpoint),
     * deferred to D4.3 with the rest of the Angular UI; for now this renders
     * the static shell (spinner). The server-side log list partial used by the
     * host/service view pages ships alongside, also for D4.3.
     */
    #[Route('/manage', name: 'devture_nagios.log.manage', methods: ['GET'])]
    public function manage(): Response
    {
        return $this->render('@DevtureNagios/log/index.html.twig');
    }
}

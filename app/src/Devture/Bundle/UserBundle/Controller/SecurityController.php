<?php

namespace Devture\Bundle\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'devture_user.login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // The form_login authenticator on the firewall intercepts the POST,
        // so this action only ever renders the form (and shows the last error).
        return $this->render('@DevtureUser/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'devture_user.logout', methods: ['GET', 'POST'])]
    public function logout(): void
    {
        // Intercepted by the firewall's logout key; never executed.
        throw new \LogicException('This method is intercepted by the logout key on the firewall.');
    }

    #[Route('/logged-out', name: 'devture_user.logged_out', methods: ['GET'])]
    public function loggedOut(): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('devture_nagios.dashboard');
        }

        return $this->render('@DevtureUser/logged_out.html.twig');
    }
}

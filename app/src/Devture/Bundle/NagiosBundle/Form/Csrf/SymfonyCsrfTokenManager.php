<?php

namespace Devture\Bundle\NagiosBundle\Form\Csrf;

use Devture\Component\Form\Token\TokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Adapts Symfony's CSRF token manager to the inlined form layer's
 * TokenManagerInterface, so the ported form binders (and the
 * render_form_csrf_token Twig helper) protect requests with
 * symfony/security-csrf instead of the legacy signed-token scheme.
 *
 * The legacy per-user salt is a no-op: Symfony's tokens are already scoped
 * to the session, which is per-user.
 */
class SymfonyCsrfTokenManager implements TokenManagerInterface
{
	public function __construct(private readonly CsrfTokenManagerInterface $csrfTokenManager)
	{
	}

	public function setSalt($salt): void
	{
		// No-op: Symfony CSRF tokens are session-scoped.
	}

	public function generate($intention)
	{
		return $this->csrfTokenManager->getToken($intention)->getValue();
	}

	public function isValid($intention, $token)
	{
		return $this->csrfTokenManager->isTokenValid(new CsrfToken($intention, (string) $token));
	}
}

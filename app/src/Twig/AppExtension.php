<?php

namespace App\Twig;

use App\Security\SecurityUser;
use Devture\Bundle\NagiosBundle\Model\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * App-level Twig helpers the ported templates rely on:
 *
 *  - is_logged_in() — whether there is an authenticated user;
 *  - get_user() — the underlying domain User (templates expect the domain
 *    model, not the SecurityUser adapter);
 *  - is_route_prefix(prefix) — whether the current route name starts with the
 *    given prefix (used to highlight the active menu entry).
 */
class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_logged_in', $this->isLoggedIn(...)),
            new TwigFunction('get_user', $this->getUser(...)),
            new TwigFunction('is_route_prefix', $this->isRoutePrefix(...)),
        ];
    }

    public function isLoggedIn(): bool
    {
        return $this->security->getUser() instanceof SecurityUser;
    }

    public function getUser(): ?User
    {
        $user = $this->security->getUser();
        if (!$user instanceof SecurityUser) {
            return null;
        }

        $domainUser = $user->getNagiosUser();

        return $domainUser instanceof User ? $domainUser : null;
    }

    public function isRoutePrefix(string $prefix): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return false;
        }

        $route = (string) $request->attributes->get('_route');

        return $route !== '' && str_starts_with($route, $prefix);
    }
}

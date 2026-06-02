<?php

namespace App\Security;

use Devture\Bundle\UserBundle\Model\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Security-layer identity adapter wrapping the domain {@see User} model.
 *
 * The domain model already exposes a getRoles() returning the legacy role
 * names ('all', 'overseer', 'configuration_management', 'sensitive',
 * 'devture_user'), which the rest of the application (AccessChecker, etc.)
 * relies on. Symfony's UserInterface::getRoles() must instead return
 * ROLE_-prefixed strings, so the two cannot share one getRoles(). Keeping the
 * Symfony identity in a thin adapter avoids that collision and keeps the
 * domain model framework-agnostic. Controllers/templates reach the underlying
 * model via getNagiosUser().
 */
class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(private readonly User $user)
    {
    }

    public function getNagiosUser(): User
    {
        return $this->user;
    }

    public function getUserIdentifier(): string
    {
        return $this->user->getUsername();
    }

    public function getPassword(): ?string
    {
        return $this->user->getPassword();
    }

    /**
     * Maps each legacy role name to a ROLE_-prefixed Symfony role and always
     * grants ROLE_USER. The master role 'all' becomes ROLE_ALL, which the
     * role hierarchy expands to every other role.
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        foreach ($this->user->getRoles() as $role) {
            $roles[] = 'ROLE_' . strtoupper($role);
        }

        return array_values(array_unique($roles));
    }
}

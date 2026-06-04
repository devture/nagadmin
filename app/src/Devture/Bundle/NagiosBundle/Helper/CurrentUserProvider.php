<?php
namespace Devture\Bundle\NagiosBundle\Helper;

use Devture\Bundle\NagiosBundle\Model\User;
use Devture\Bundle\UserBundle\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Resolves the current authenticated domain User (not the SecurityUser
 * adapter), for the access checks the controllers and form binders perform.
 */
class CurrentUserProvider {

	public function __construct(private readonly Security $security) {
	}

	public function getUser(): ?User {
		$user = $this->security->getUser();
		if (!$user instanceof SecurityUser) {
			return null;
		}

		$domainUser = $user->getNagiosUser();

		return $domainUser instanceof User ? $domainUser : null;
	}

}

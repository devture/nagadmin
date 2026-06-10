<?php

namespace Devture\Bundle\UserBundle\Security;

use Devture\Bundle\UserBundle\Repository\UserRepositoryInterface;
use Devture\Component\DBAL\Exception\NotFound;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Loads {@see SecurityUser} identities from the native Mongo-backed user
 * repository, keyed by username.
 *
 * @implements UserProviderInterface<SecurityUser>
 */
class UserProvider implements UserProviderInterface
{
	public function __construct(private readonly UserRepositoryInterface $repository)
	{
	}

	public function loadUserByIdentifier(string $identifier): UserInterface
	{
		try {
			$user = $this->repository->findByUsername($identifier);
		} catch (NotFound) {
			throw new UserNotFoundException(sprintf('No user found for username "%s".', $identifier));
		}

		return new SecurityUser($user);
	}

	public function refreshUser(UserInterface $user): UserInterface
	{
		if (!$user instanceof SecurityUser) {
			throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
		}

		return $this->loadUserByIdentifier($user->getUserIdentifier());
	}

	public function supportsClass(string $class): bool
	{
		return SecurityUser::class === $class || is_subclass_of($class, SecurityUser::class);
	}
}

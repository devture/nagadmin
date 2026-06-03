<?php

namespace Devture\Bundle\UserBundle\Form;

use Devture\Bundle\NagiosBundle\Form\Csrf\SymfonyCsrfTokenManager;
use Devture\Bundle\UserBundle\Security\SecurityUser;
use Devture\Bundle\UserBundle\Model\User;
use Devture\Bundle\UserBundle\Validator\UserValidator;
use Devture\Component\Form\Binder\SetterRequestBinder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Binds the user-management form onto a User model: whitelisted fields via
 * setters, password hashed through the Symfony hasher (only when provided, so
 * editing without a new password preserves the old hash), validated by
 * UserValidator and CSRF-protected through the security-csrf adapter.
 */
class UserFormBinder extends SetterRequestBinder
{
    public function __construct(
        UserValidator $validator,
        private readonly UserPasswordHasherInterface $passwordHasher,
        SymfonyCsrfTokenManager $csrfTokenManager,
    ) {
        parent::__construct($validator);
        $this->setCsrfProtection($csrfTokenManager, 'user');
    }

    protected function doBindRequest($entity, Request $request, array $options = array())
    {
        // Clear the roles first. If the request does not contain a "roles" value,
        // binding below would just skip it and keep them as they were, which is
        // not what we want.
        $entity->setRoles(array());

        $whitelisted = array('username', 'email', 'name', 'roles');
        $this->bindWhitelisted($entity, $request->request->all(), $whitelisted);

        $password = $request->request->get('password');
        if ($password !== '' && $password !== null) {
            if (strlen($password) > 4096) {
                $this->getViolations()->add('password', 'The password is too long.');
            } else {
                /** @var User $entity */
                $entity->setPassword($this->passwordHasher->hashPassword(new SecurityUser($entity), $password));
            }
        }
    }
}

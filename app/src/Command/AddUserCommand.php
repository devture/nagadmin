<?php

namespace App\Command;

use App\Security\SecurityUser;
use Devture\Bundle\NagiosBundle\Model\User;
use Devture\Bundle\UserBundle\Repository\UserRepositoryInterface;
use Devture\Component\DBAL\Exception\NotFound;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'devture-user:add',
    description: 'Adds a new user account (with full privileges).',
)]
class AddUserCommand extends Command
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'The username of the new account.');
        $this->addArgument('email', InputArgument::OPTIONAL, 'The email address of the new account.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');
        $email = $input->getArgument('email');

        /** @var User $entity */
        $entity = $this->repository->createModel([]);

        try {
            $this->repository->findByUsername($username);
            $io->error(sprintf('A user with the username %s already exists.', $username));

            return Command::FAILURE;
        } catch (NotFound) {
            // Good, the username is free.
        }
        $entity->setUsername($username);

        if ($email) {
            try {
                $this->repository->findByEmail($email);
                $io->error(sprintf('A user with the email %s already exists.', $email));

                return Command::FAILURE;
            } catch (NotFound) {
                // Good, the email is free.
            }
            $entity->setEmail($email);
        }

        $entity->setName((string) $io->ask('Name (not required)'));

        $password = (string) $io->askHidden('Enter a password');
        if ($password === '') {
            $io->error('The password cannot be empty.');

            return Command::FAILURE;
        }
        $entity->setPassword($this->passwordHasher->hashPassword(new SecurityUser($entity), $password));

        $entity->setRoles([User::ROLE_MASTER]);

        $this->repository->add($entity);

        $io->success(sprintf('User %s added successfully.', $username));

        return Command::SUCCESS;
    }
}

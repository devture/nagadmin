<?php

namespace Devture\Bundle\UserBundle\ConsoleCommand;

use Devture\Bundle\UserBundle\Security\SecurityUser;
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
    name: 'devture-user:change-password',
    description: "Changes an existing user account's password.",
)]
class ChangeUserPasswordCommand extends Command
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'The username whose password to change.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');

        try {
            $entity = $this->repository->findByUsername($username);
        } catch (NotFound) {
            $io->error(sprintf('Cannot find user: %s', $username));

            return Command::FAILURE;
        }

        $password = (string) $io->askHidden('Enter a password');
        if ($password === '') {
            $io->error('The password cannot be empty.');

            return Command::FAILURE;
        }
        $entity->setPassword($this->passwordHasher->hashPassword(new SecurityUser($entity), $password));

        $this->repository->update($entity);

        // The legacy vendored command returned null here, which symfony/console
        // rejected with exit code 255 even though the change succeeded.
        $io->success(sprintf('Password for user %s updated successfully.', $username));

        return Command::SUCCESS;
    }
}

<?php

namespace Devture\Bundle\NagiosBundle\ConsoleCommand;

use Devture\Bundle\UserBundle\Security\SecurityUser;
use Devture\Bundle\UserBundle\Security\UserProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AsCommand(
    name: 'nagadmin:debug:auth',
    description: 'Loads a user through the security provider and verifies a password against the stored hash.',
)]
class DebugAuthCommand extends Command
{
    public function __construct(
        private readonly UserProvider $userProvider,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::OPTIONAL, 'The username to load.', 'testlogin');
        $this->addArgument('password', InputArgument::OPTIONAL, 'The plaintext password to verify.', 'testpass123');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');

        try {
            $user = $this->userProvider->loadUserByIdentifier($username);
        } catch (UserNotFoundException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if (!$user instanceof SecurityUser) {
            $io->error('The provider returned an unexpected identity type.');

            return Command::FAILURE;
        }

        $valid = $this->passwordHasher->isPasswordValid($user, $password);
        $needsRehash = $this->passwordHasher->needsRehash($user);

        $io->table(['Field', 'Value'], [
            ['identifier', $user->getUserIdentifier()],
            ['stored hash', $user->getPassword()],
            ['symfony roles', implode(', ', $user->getRoles())],
            ['password valid', $valid ? 'yes' : 'no'],
            ['needs rehash', $needsRehash ? 'yes' : 'no'],
        ]);

        if (!$valid) {
            $io->error('Password verification FAILED.');

            return Command::FAILURE;
        }

        $io->success('Password verified against the stored hash.');

        return Command::SUCCESS;
    }
}

<?php

namespace App\Command;

use App\Security\UserProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AsCommand(
    name: 'nagadmin:debug:access',
    description: 'Shows which security roles a user is granted, exercising the role hierarchy.',
)]
class DebugAccessCommand extends Command
{
    private const ROLES = [
        'ROLE_USER',
        'ROLE_OVERSEER',
        'ROLE_CONFIGURATION_MANAGEMENT',
        'ROLE_SENSITIVE',
        'ROLE_DEVTURE_USER',
        'ROLE_ALL',
    ];

    public function __construct(
        private readonly UserProvider $userProvider,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::OPTIONAL, 'The username to inspect.', 'testlogin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');

        try {
            $user = $this->userProvider->loadUserByIdentifier($username);
        } catch (UserNotFoundException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));

        $rows = [];
        foreach (self::ROLES as $role) {
            $rows[] = [$role, $this->authorizationChecker->isGranted($role) ? 'granted' : '—'];
        }

        $io->section(sprintf('User "%s" — assigned roles: %s', $username, implode(', ', $user->getRoles())));
        $io->table(['Role', 'Decision'], $rows);

        return Command::SUCCESS;
    }
}

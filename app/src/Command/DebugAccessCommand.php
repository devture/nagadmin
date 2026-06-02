<?php

namespace App\Command;

use App\Security\SecurityUser;
use App\Security\UserProvider;
use App\Security\Voter\NagiosAccessVoter;
use Devture\Bundle\NagiosBundle\Model\User;
use Devture\Bundle\NagiosBundle\Repository\HostRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AsCommand(
    name: 'nagadmin:debug:access',
    description: 'Shows the roles and per-entity access decisions for a user, exercising the role hierarchy and the access voter.',
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

    private const CAPABILITIES = [
        NagiosAccessVoter::CONFIGURATION_MANAGEMENT,
        NagiosAccessVoter::CREATE_HOST,
        NagiosAccessVoter::MANAGE_HOSTS,
        NagiosAccessVoter::CREATE_CONTACT,
        NagiosAccessVoter::MANAGE_CONTACTS,
    ];

    public function __construct(
        private readonly UserProvider $userProvider,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly HostRepository $hostRepository,
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
            $dbUser = $this->userProvider->loadUserByIdentifier($username);
        } catch (UserNotFoundException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        // Grab one host (if any) to exercise the subject-based voter rules.
        $hosts = $this->hostRepository->findAll();
        $host = $hosts[0] ?? null;

        $this->report($io, $dbUser, $host);

        // A synthetic, non-persisted "sensitive"-only identity, to show that the
        // voter denies capabilities the user does not hold (i.e. it is the
        // domain user — not the master role — flowing through).
        $limited = new SecurityUser(new User(['username' => 'synthetic-sensitive', 'roles' => ['sensitive']]));
        $this->report($io, $limited, $host);

        return Command::SUCCESS;
    }

    private function report(SymfonyStyle $io, SecurityUser $user, ?object $host): void
    {
        $this->tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));

        $io->section(sprintf('User "%s" — assigned roles: %s', $user->getUserIdentifier(), implode(', ', $user->getRoles())));

        $roleRows = [];
        foreach (self::ROLES as $role) {
            $roleRows[] = [$role, $this->granted($role)];
        }
        $io->table(['Role', 'Decision'], $roleRows);

        $capabilityRows = [];
        foreach (self::CAPABILITIES as $capability) {
            $capabilityRows[] = [$capability, $this->granted($capability)];
        }
        if ($host !== null) {
            $capabilityRows[] = [NagiosAccessVoter::VIEW . ' (host)', $this->granted(NagiosAccessVoter::VIEW, $host)];
            $capabilityRows[] = [NagiosAccessVoter::MANAGE . ' (host)', $this->granted(NagiosAccessVoter::MANAGE, $host)];
        }
        $io->table(['Voter attribute', 'Decision'], $capabilityRows);
    }

    private function granted(string $attribute, mixed $subject = null): string
    {
        return $this->authorizationChecker->isGranted($attribute, $subject) ? 'granted' : '—';
    }
}

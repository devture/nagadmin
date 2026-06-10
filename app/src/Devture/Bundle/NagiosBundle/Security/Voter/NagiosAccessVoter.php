<?php

namespace Devture\Bundle\NagiosBundle\Security\Voter;

use Devture\Bundle\UserBundle\Security\SecurityUser;
use Devture\Bundle\NagiosBundle\Helper\AccessChecker;
use Devture\Bundle\NagiosBundle\Log\LogEntry;
use Devture\Bundle\NagiosBundle\Model\Contact;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Model\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Bridges the framework-agnostic {@see AccessChecker} into Symfony's
 * authorization layer, so controllers and templates can use the standard
 * isGranted() / denyAccessUnlessGranted() API for the fine-grained,
 * per-entity access rules.
 *
 * Subject-based attributes (VIEW / MANAGE) carry a Host, Service, Contact or
 * LogEntry; the capability attributes carry no subject.
 *
 * @extends Voter<string, Host|Service|Contact|LogEntry|null>
 */
class NagiosAccessVoter extends Voter
{
	public const VIEW = 'NAGIOS_VIEW';
	public const MANAGE = 'NAGIOS_MANAGE';

	public const CREATE_HOST = 'NAGIOS_CREATE_HOST';
	public const MANAGE_HOSTS = 'NAGIOS_MANAGE_HOSTS';
	public const CREATE_CONTACT = 'NAGIOS_CREATE_CONTACT';
	public const MANAGE_CONTACTS = 'NAGIOS_MANAGE_CONTACTS';
	public const CONFIGURATION_MANAGEMENT = 'NAGIOS_CONFIGURATION_MANAGEMENT';

	private const SUBJECTLESS = [
		self::CREATE_HOST,
		self::MANAGE_HOSTS,
		self::CREATE_CONTACT,
		self::MANAGE_CONTACTS,
		self::CONFIGURATION_MANAGEMENT,
	];

	public function __construct(private readonly AccessChecker $accessChecker)
	{
	}

	protected function supports(string $attribute, mixed $subject): bool
	{
		if (in_array($attribute, [self::VIEW, self::MANAGE], true)) {
			return $subject instanceof Host
				|| $subject instanceof Service
				|| $subject instanceof Contact
				|| $subject instanceof LogEntry;
		}

		return in_array($attribute, self::SUBJECTLESS, true) && $subject === null;
	}

	protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
	{
		$securityUser = $token->getUser();
		if (!$securityUser instanceof SecurityUser) {
			return false;
		}

		$user = $securityUser->getNagiosUser();
		if (!$user instanceof User) {
			return false;
		}

		return match (true) {
			$attribute === self::CONFIGURATION_MANAGEMENT => $this->accessChecker->canUserDoConfigurationManagement($user),
			$attribute === self::CREATE_HOST => $this->accessChecker->canUserCreateHosts($user),
			$attribute === self::MANAGE_HOSTS => $this->accessChecker->canUserManageHosts($user),
			$attribute === self::CREATE_CONTACT => $this->accessChecker->canUserCreateContacts($user),
			$attribute === self::MANAGE_CONTACTS => $this->accessChecker->canUserManageContacts($user),

			$attribute === self::VIEW && $subject instanceof Host => $this->accessChecker->canUserViewHost($user, $subject),
			$attribute === self::MANAGE && $subject instanceof Host => $this->accessChecker->canUserManageHost($user, $subject),
			$attribute === self::VIEW && $subject instanceof Service => $this->accessChecker->canUserViewService($user, $subject),
			$attribute === self::MANAGE && $subject instanceof Service => $this->accessChecker->canUserManageService($user, $subject),
			$attribute === self::MANAGE && $subject instanceof Contact => $this->accessChecker->canUserManageContact($user, $subject),
			$attribute === self::VIEW && $subject instanceof LogEntry => $this->accessChecker->canUserViewLogEntry($user, $subject),

			default => false,
		};
	}
}

<?php
namespace Devture\Bundle\NagiosBundle\Helper;

use Devture\Bundle\NagiosBundle\Model\User;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Model\Contact;
use Devture\Bundle\NagiosBundle\Log\LogEntry;

class AccessChecker {

	public function canUserViewHost(?User $user, Host $host): bool {
		if ($user === null) {
			return false;
		}

		if ($this->canUserManageHost($user, $host)) {
			return true;
		}

		if ($user->hasRole('overseer')) {
			return true;
		}

		foreach ($host->getGroups() as $groupName) {
			if ($user->hasGroup($groupName)) {
				return true;
			}
		}

		return false;
	}

	public function canUserCreateHosts(?User $user): bool {
		return $this->canUserDoConfigurationManagement($user);
	}

	public function canUserManageHosts(?User $user): bool {
		return $this->canUserDoConfigurationManagement($user);
	}

	public function canUserManageHost(?User $user, Host $host): bool {
		return $this->canUserDoConfigurationManagement($user);
	}

	public function canUserViewService(?User $user, Service $service): bool {
		return $this->canUserViewHost($user, $service->getHost());
	}

	public function canUserManageService(?User $user, Service $service): bool {
		return $this->canUserManageHost($user, $service->getHost());
	}

	public function canUserCreateContacts(?User $user): bool {
		return $this->canUserDoConfigurationManagement($user);
	}

	public function canUserManageContacts(?User $user): bool {
		return true;
	}

	public function canUserManageContact(?User $user, Contact $contact): bool {
		if ($user === null) {
			return false;
		}
		return $this->canUserDoConfigurationManagement($user) || ($contact->getUser() === $user);
	}

	public function canUserViewLogEntry(?User $user, LogEntry $logEntry): bool {
		if ($user === null) {
			return false;
		}

		if ($this->hasUserGlobalAccess($user)) {
			return true;
		}

		if ($user->hasRole('sensitive')) {
			return true;
		}

		if ($logEntry->getType() === 'SYSTEM') {
			//Information not for the common folk.
			return false;
		}

		if ($logEntry->getHost() instanceof Host) {
			return $this->canUserViewHost($user, $logEntry->getHost());
		}

		return false;
	}

	public function canUserDoConfigurationManagement(?User $user): bool {
		if ($user === null) {
			return false;
		}
		return $user->hasRole('configuration_management') || $this->hasUserGlobalAccess($user);
	}

	protected function hasUserGlobalAccess(User $user): bool {
		return $user->hasRole(User::ROLE_MASTER);
	}

}

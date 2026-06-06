<?php
namespace Devture\Bundle\NagiosBundle\ApiModelBridge;

use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Helper\AccessChecker;
use Devture\Bundle\NagiosBundle\Helper\CurrentUserProvider;

class HostBridge {

	private $currentUserProvider;
	private $accessChecker;

	public function __construct(CurrentUserProvider $currentUserProvider, AccessChecker $accessChecker) {
		$this->currentUserProvider = $currentUserProvider;
		$this->accessChecker = $accessChecker;
	}

	public function export(Host $entity) {
		return array(
			'id' => (string) $entity->getId(),
			'name' => $entity->getName(),
			'address' => $entity->getAddress(),
			'editable' => $this->accessChecker->canUserManageHost($this->currentUserProvider->getUser(), $entity),
		);
	}

}

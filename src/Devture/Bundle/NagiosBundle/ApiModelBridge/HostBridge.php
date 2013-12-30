<?php
namespace Devture\Bundle\NagiosBundle\ApiModelBridge;

use Devture\Bundle\UserBundle\AccessControl\AccessControl;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Helper\AccessChecker;

class HostBridge {

	private $accessControl;
	private $accessChecker;

	public function __construct(AccessControl $accessControl, AccessChecker $accessChecker) {
		$this->accessControl = $accessControl;
		$this->accessChecker = $accessChecker;
	}

	public function export(Host $entity) {
		return array(
			'id' => (string) $entity->getId(),
			'name' => $entity->getName(),
			'address' => $entity->getAddress(),
			'editable' => $this->accessChecker->canUserManageHost($this->accessControl->getUser(), $entity),
		);
	}

}
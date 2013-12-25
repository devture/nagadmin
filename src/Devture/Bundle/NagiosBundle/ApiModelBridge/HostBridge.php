<?php
namespace Devture\Bundle\NagiosBundle\ApiModelBridge;

use Devture\Bundle\NagiosBundle\Model\Host;

class HostBridge {

	public function export(Host $entity) {
		return array(
			'id' => (string) $entity->getId(),
			'name' => $entity->getName(),
			'address' => $entity->getAddress(),
		);
	}

}
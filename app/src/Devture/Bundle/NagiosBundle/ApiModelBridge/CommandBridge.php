<?php
namespace Devture\Bundle\NagiosBundle\ApiModelBridge;

use Devture\Bundle\NagiosBundle\Model\Command;

class CommandBridge {

	public function export(Command $entity) {
		return array(
			'id' => (string) $entity->getId(),
			'title' => $entity->getTitle(),
		);
	}

}
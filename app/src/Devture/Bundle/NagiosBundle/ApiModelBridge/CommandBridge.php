<?php
namespace Devture\Bundle\NagiosBundle\ApiModelBridge;

use Devture\Bundle\NagiosBundle\Model\Command;

class CommandBridge {

	/**
	 * @return array<string, mixed>
	 */
	public function export(Command $entity) {
		return array(
			'id' => (string) $entity->getId(),
			'title' => $entity->getTitle(),
		);
	}

}

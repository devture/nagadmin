<?php
namespace Devture\Bundle\NagiosBundle\ApiModelBridge;

use Devture\Bundle\NagiosBundle\Log\LogEntry;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Model\Service;

class LogBridge {

	public function export(LogEntry $entity) {
		return array(
			'id' => (string) $entity->getId(),
			'type' => $entity->getType(),
			'timestamp' => $entity->getTimestamp(),
			'value' => $entity->getValue(),
			'host' => array(
				'id' => ($entity->getHost() instanceof Host ? (string) $entity->getHost()->getId() : null),
				'address' => ($entity->getHost() instanceof Host ? $entity->getHost()->getAddress() : null),
			),
			'service' => array(
				'id' => ($entity->getService() instanceof Service ? (string) $entity->getService()->getId() : null),
			),
		);
	}

}
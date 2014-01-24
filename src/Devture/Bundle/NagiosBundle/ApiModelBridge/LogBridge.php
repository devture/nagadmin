<?php
namespace Devture\Bundle\NagiosBundle\ApiModelBridge;

use Devture\Bundle\NagiosBundle\Log\LogEntry;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Model\Service;

class LogBridge {

	public function export(LogEntry $entity) {
		$host = $entity->getHost();
		$service = $entity->getService();

		return array(
			'id' => (string) $entity->getId(),
			'type' => $entity->getType(),
			'timestamp' => $entity->getTimestamp(),
			'value' => $entity->getValue(),
			'host' => array(
				'id' => ($host instanceof Host ? (string) $host->getId() : null),
				'address' => ($host instanceof Host ? $host->getAddress() : null),
			),
			'service' => array(
				'id' => ($service instanceof Service ? (string) $service->getId() : null),
			),
		);
	}

}
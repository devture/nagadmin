<?php
namespace Devture\Bundle\NagiosBundle\ApiModelBridge;

use Devture\Bundle\NagiosBundle\Status\ServiceStatus;

class ServiceStatusBridge {

	public function export(ServiceStatus $entity) {
		return $entity->getDirectives();
	}

}
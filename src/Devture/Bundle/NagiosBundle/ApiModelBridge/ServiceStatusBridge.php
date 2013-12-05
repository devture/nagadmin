<?php
namespace Devture\Bundle\NagiosBundle\ApiModelBridge;

use Devture\Bundle\NagiosBundle\Status\ServiceStatus;

class ServiceStatusBridge {

	public function export(ServiceStatus $entity) {
		static $exportedDirectives = array(
			'current_attempt',
			'current_state',
			'has_been_checked',
			'last_check',
			'last_hard_state',
			'last_hard_state_change',
			'last_state_change',
			'max_attempts',
			'next_check',
			'performance_data',
			'plugin_output',
		);

		$status = array();
		foreach ($entity->getDirectives() as $directiveName => $directiveValue) {
			if (in_array($directiveName, $exportedDirectives)) {
				$status[$directiveName] = $directiveValue;
			}
		}
		return $status;
	}

}
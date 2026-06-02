<?php
namespace Devture\Bundle\NagiosBundle\NagiosCommand;

use Devture\Bundle\NagiosBundle\Model\Service;

class Manager {

	private $submitter;

	public function __construct(Submitter $submitter) {
		$this->submitter = $submitter;
	}

	public function scheduleServiceCheck(Service $service) {
		$timeNow = time();
		$command = sprintf(
			'[%d] SCHEDULE_SVC_CHECK;%s;%s;%d',
			$timeNow,
			$service->getHost()->getName(),
			$service->getName(),
			$timeNow
		);
		$this->submitter->submit($command);
	}

}
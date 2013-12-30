<?php
namespace Devture\Bundle\NagiosBundle\Controller\Api;

use Devture\Bundle\NagiosBundle\Log\LogEntry;

class LogApiController extends \Devture\Bundle\NagiosBundle\Controller\BaseController {

	public function listAction($ifNewerThanId) {
		$items = $this->getLogFetcher()->fetch();

		if ($ifNewerThanId !== null && count($items) > 0) {
			if ($items[0]->getId() === $ifNewerThanId) {
				$items = array();
			}
		}


		$accessChecker = $this->getAccessChecker();
		$user = $this->getUser();

		$items = array_values(array_filter($items, function (LogEntry $logEntry) use ($accessChecker, $user) {
			return $accessChecker->canUserViewLogEntry($user, $logEntry);
		}));

		/** @var $logBridge \Devture\Bundle\NagiosBundle\ApiModelBridge\LogBridge */
		$logBridge = $this->getNs('log.api_model_bridge');

		$result = array_map(function (LogEntry $entity) use ($logBridge) {
			return $logBridge->export($entity);
		}, $items);

		return $this->json($result);
	}

}
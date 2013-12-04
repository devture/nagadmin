<?php
namespace Devture\Bundle\NagiosBundle\Controller\Api;

use Devture\Bundle\NagiosBundle\Model\Command;

class CommandApiController extends \Devture\Bundle\NagiosBundle\Controller\BaseController {

	public function listAction($type) {
		$commands = $this->getCommandRepository()->findAllByType($type);

		/** @var $commandBridge \Devture\Bundle\NagiosBundle\ApiModelBridge\CommandBridge */
		$commandBridge = $this->getNs('command.api_model_bridge');

		$result = array_map(function (Command $command) use ($commandBridge) {
			return $commandBridge->export($command);
		}, $commands);

		return $this->json($result);
	}

}
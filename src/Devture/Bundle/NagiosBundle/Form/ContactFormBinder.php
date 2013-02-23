<?php
namespace Devture\Bundle\NagiosBundle\Form;

use Symfony\Component\HttpFoundation\Request;
use Devture\Bundle\SharedBundle\Form\SetterRequestBinder;
use Devture\Bundle\SharedBundle\Validator\BaseValidator;
use Devture\Bundle\SharedBundle\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Model\Contact;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Repository\TimePeriodRepository;
use Devture\Bundle\NagiosBundle\Repository\CommandRepository;

class ContactFormBinder extends SetterRequestBinder {

	private $timePeriodRepository;
	private $commandRepository;
	private $validator;

	public function __construct(TimePeriodRepository $timePeriodRepository, CommandRepository $commandRepository, BaseValidator $validator) {
		parent::__construct();
		$this->timePeriodRepository = $timePeriodRepository;
		$this->commandRepository = $commandRepository;
		$this->validator = $validator;
	}

	/**
	 * @param string $id
	 * @return Command
	 */
	private function getNotificationCommandById($id) {
		$command = $this->commandRepository->find($id);
		if ($command->getType() !== Command::TYPE_SERVICE_NOTIFICATION) {
			throw new NotFound('Bad command type.');
		}
		return $command;
	}

	protected function doBindRequest(Contact $entity, Request $request, array $options = array()) {
		$whitelisted = array('name', 'email');
		$this->bindWhitelisted($entity, $request->request->all(), $whitelisted);

		try {
			$timePeriod = $this->timePeriodRepository->find($request->request->get('timePeriodId'));
			$entity->setTimePeriod($timePeriod);

		} catch (NotFound $e) {
			$this->violations->add('timePeriod', 'Cannot find the selected time period.');
		}

		try {
			$command = $this->getNotificationCommandById($request->request->get('serviceNotificationCommandId'));
			$entity->setServiceNotificationCommand($command);
		} catch (NotFound $e) {
			$this->violations->add('serviceNotificationCommand', 'Cannot find the selected service notification command.');
		}

		$entity->clearAddresses();
		foreach ((array)$request->request->get('addresses') as $slot => $address) {
			if ($address) {
				$entity->addAddress($slot, $address);
			}
		}

		$this->violations->merge($this->validator->validate($entity));
	}

}
<?php
namespace Devture\Bundle\NagiosBundle\Form;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\Form\Binder\SetterRequestBinder;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Model\Contact;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Repository\TimePeriodRepository;
use Devture\Bundle\NagiosBundle\Repository\CommandRepository;
use Devture\Bundle\NagiosBundle\Validator\ContactValidator;
use Devture\Bundle\UserBundle\Repository\UserRepositoryInterface;
use Devture\Bundle\NagiosBundle\Helper\AccessChecker;
use Devture\Bundle\UserBundle\AccessControl\AccessControl;

class ContactFormBinder extends SetterRequestBinder {

	private $timePeriodRepository;
	private $commandRepository;
	private $userRepository;
	private $accessChecker;
	private $accessControl;

	public function __construct(TimePeriodRepository $timePeriodRepository, CommandRepository $commandRepository,
								UserRepositoryInterface $userRepository, AccessChecker $accessChecker, AccessControl $accessControl,
								ContactValidator $validator) {
		parent::__construct($validator);
		$this->timePeriodRepository = $timePeriodRepository;
		$this->commandRepository = $commandRepository;
		$this->userRepository = $userRepository;
		$this->accessChecker = $accessChecker;
		$this->accessControl = $accessControl;
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

	/**
	 * @param Contact $entity
	 * @param Request $request
	 * @param array $options
	 */
	protected function doBindRequest($entity, Request $request, array $options = array()) {
		$whitelisted = array('name', 'email');
		$this->bindWhitelisted($entity, $request->request->all(), $whitelisted);

		if ($this->accessChecker->canUserDoConfigurationManagement($this->accessControl->getUser())) {
			try {
				$user = $this->userRepository->find($request->request->get('userId'));
				$entity->setUser($user);
			} catch (NotFound $e) {
				$entity->setUser(null);
			}
		}

		try {
			$timePeriod = $this->timePeriodRepository->find($request->request->get('timePeriodId'));
			$entity->setTimePeriod($timePeriod);
		} catch (NotFound $e) {
			$this->getViolations()->add('timePeriod', 'Cannot find the selected time period.');
		}

		try {
			$command = $this->getNotificationCommandById($request->request->get('serviceNotificationCommandId'));
			$entity->setServiceNotificationCommand($command);
		} catch (NotFound $e) {
			$this->getViolations()->add('serviceNotificationCommand', 'Cannot find the selected service notification command.');
		}

		$entity->clearAddresses();
		foreach ((array)$request->request->get('addresses') as $slot => $address) {
			if ($address) {
				$entity->addAddress($slot, $address);
			}
		}
	}

}

<?php
namespace Devture\Bundle\NagiosBundle\Form;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\Form\Binder\SetterRequestBinder;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Model\ServiceCommandArgument;
use Devture\Bundle\NagiosBundle\Repository\ContactRepository;
use Devture\Bundle\NagiosBundle\Validator\ServiceValidator;

class ServiceFormBinder extends SetterRequestBinder {

	private $contactRepository;

	public function __construct(ContactRepository $contactRepository, ServiceValidator $validator) {
		parent::__construct($validator);
		$this->contactRepository = $contactRepository;
	}

	/**
	 * @param Service $entity
	 * @param Request $request
	 * @param array $options
	 */
	protected function doBindRequest($entity, Request $request, array $options = array()) {
		$whitelisted = array(
			'name',
			'maxCheckAttempts',
			'checkInterval',
			'retryInterval',
			'notificationInterval',
		);
		$this->bindWhitelisted($entity, $request->request->all(), $whitelisted);

		$entity->setEnabled($request->request->get('enabled') === '1');

		$entity->clearArguments();
		$command = $entity->getCommand();
		if ($command instanceof Command) {
			$expectedArgumentsCount = $command->getLineArgumentsCount();
			$argumentNumber = 0;
			foreach ((array)$request->request->get('arguments') as $argumentId => $argumentData) {
				$argumentNumber += 1;
				if ($argumentNumber > $expectedArgumentsCount) {
					break;
				}
				$argument = new ServiceCommandArgument();
				$argument->setId($argumentId);
				$argument->setValue(isset($argumentData['value']) ? trim($argumentData['value']) : '');
				$entity->addArgument($argument);
			}
		}

		$entity->clearContacts();
		foreach ((array)$request->request->get('contactsIds') as $contactId) {
			try {
				$entity->addContact($this->contactRepository->find($contactId));
			} catch (NotFound $e) {
				$this->getViolations()->add('contacts', 'Cannot find contact.');
			}
		}
	}

}

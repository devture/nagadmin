<?php
namespace Devture\Bundle\NagiosBundle\Form;

use Symfony\Component\HttpFoundation\Request;
use Devture\Bundle\SharedBundle\Form\SetterRequestBinder;
use Devture\Bundle\SharedBundle\Validator\BaseValidator;
use Devture\Bundle\SharedBundle\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Model\ServiceCommandArgument;
use Devture\Bundle\NagiosBundle\Repository\HostRepository;
use Devture\Bundle\NagiosBundle\Repository\ContactRepository;

class ServiceFormBinder extends SetterRequestBinder {

	private $hostRepository;
	private $contactRepository;
	private $validator;

	public function __construct(HostRepository $hostRepository, ContactRepository $contactRepository, BaseValidator $validator) {
		parent::__construct();
		$this->hostRepository = $hostRepository;
		$this->contactRepository = $contactRepository;
		$this->validator = $validator;
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
			'checkInterval', 'retryInterval',
			'notificationInterval',
		);
		$this->bindWhitelisted($entity, $request->request->all(), $whitelisted);

		$entity->setEnabled($request->request->get('enabled') === '1');

		try {
			$host = $this->hostRepository->find($request->request->get('hostId'));
			$entity->setHost($host);
		} catch (NotFound $e) {
			$this->violations->add('host', 'Cannot find the selected host.');
		}

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
				$this->violations->add('contacts', 'Cannot find contact.');
			}
		}

		$this->violations->merge($this->validator->validate($entity));
	}

}

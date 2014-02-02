<?php
namespace Devture\Bundle\NagiosBundle\Repository;

use Doctrine\MongoDB\Database;
use Devture\Component\DBAL\Repository\BaseMongoRepository;
use Devture\Bundle\NagiosBundle\Model\Service;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Model\Contact;
use Devture\Bundle\NagiosBundle\Repository\HostRepository;
use Devture\Bundle\NagiosBundle\Repository\CommandRepository;

class ServiceRepository extends BaseMongoRepository {

	private $hostRepository;
	private $commandRepository;
	private $contactRepository;

	public function __construct(HostRepository $hostRepository, CommandRepository $commandRepository, ContactRepository $contactRepository, Database $db) {
		parent::__construct($db);
		$this->hostRepository = $hostRepository;
		$this->commandRepository = $commandRepository;
		$this->contactRepository = $contactRepository;
	}

	protected function getModelClass() {
		return '\\Devture\Bundle\\NagiosBundle\\Model\\Service';
	}

	protected function getCollectionName() {
		return 'service';
	}

	/**
	 * @see \Devture\Component\DBAL\Repository\BaseRepository::hydrateModel()
	 */
	protected function hydrateModel(array $data) {
		$model = new Service($data);

		if (isset($data['hostId'])) {
			$model->setHost($this->hostRepository->find($data['hostId']));
		}

		if (isset($data['commandId'])) {
			$model->setCommand($this->commandRepository->find($data['commandId']));
		}

		if (isset($data['contactsIds']) && is_array($data['contactsIds'])) {
			foreach ($data['contactsIds'] as $contactId) {
				$model->addContact($this->contactRepository->find($contactId));
			}
		}

		return $model;
	}

	/**
	 * @see \Devture\Component\DBAL\Repository\BaseRepository::exportModel()
	 */
	protected function exportModel($model) {
		$export = parent::exportModel($model);

		$host = $model->getHost();
		$export['hostId'] = $host instanceof Host ? $host->getId() : null;

		$command = $model->getCommand();
		$export['commandId'] = $command instanceof Command ? $command->getId() : null;

		$export['contactsIds'] = array_map(function (Contact $contact) {
			return $contact->getId();
		}, $model->getContacts());

		return $export;
	}

	public function findByHost(Host $host) {
		return $this->findBy(array('hostId' => $host->getId()), array('sort' => array('name' => 1)));
	}

	public function countByHost(Host $host) {
		return $this->db->selectCollection($this->getCollectionName())->count(array('hostId' => $host->getId()));
	}

	public function findByCommand(Command $command) {
		return $this->findBy(array('commandId' => $command->getId()), array('sort' => array('name' => 1)));
	}

	public function findByContact(Contact $contact) {
		return $this->findBy(array('contactsIds' => $contact->getId()));
	}

}
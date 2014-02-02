<?php
namespace Devture\Bundle\NagiosBundle\Repository;

use Doctrine\MongoDB\Database;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Devture\Component\DBAL\Repository\BaseMongoRepository;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\UserBundle\Repository\UserRepositoryInterface;
use Devture\Bundle\NagiosBundle\Model\Contact;
use Devture\Bundle\NagiosBundle\Model\TimePeriod;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Model\User;
use Devture\Bundle\NagiosBundle\Repository\TimePeriodRepository;
use Devture\Bundle\NagiosBundle\Repository\CommandRepository;
use Devture\Bundle\NagiosBundle\Event\Events;
use Devture\Bundle\NagiosBundle\Event\ModelEvent;

class ContactRepository extends BaseMongoRepository {

	private $dispatcher;
	private $timePeriodRepository;
	private $commandRepository;
	private $userRepository;

	public function __construct(EventDispatcherInterface $dispatcher, TimePeriodRepository $timePeriodRepository,
								CommandRepository $commandRepository, UserRepositoryInterface $userRepository, Database $db) {
		parent::__construct($db);
		$this->dispatcher = $dispatcher;
		$this->timePeriodRepository = $timePeriodRepository;
		$this->commandRepository = $commandRepository;
		$this->userRepository = $userRepository;
	}

	protected function getModelClass() {
		return '\\Devture\Bundle\\NagiosBundle\\Model\\Contact';
	}

	protected function getCollectionName() {
		return 'contact';
	}

	/**
	 * @see \Devture\Component\DBAL\Repository\BaseRepository::hydrateModel()
	 */
	protected function hydrateModel(array $data) {
		$model = new Contact($data);

		if (isset($data['timePeriodId'])) {
			$model->setTimePeriod($this->timePeriodRepository->find($data['timePeriodId']));
		}

		if (isset($data['serviceNotificationCommandId'])) {
			$model->setServiceNotificationCommand($this->commandRepository->find($data['serviceNotificationCommandId']));
		}

		if (isset($data['userId'])) {
			try {
				$model->setUser($this->userRepository->find($data['userId']));
			} catch (NotFound $e) {
				//User got deleted or something
			}
		}

		return $model;
	}

	/**
	 * @see \Devture\Component\DBAL\Repository\BaseRepository::exportModel()
	 * @param $model Contact
	 */
	protected function exportModel($model) {
		$export = parent::exportModel($model);

		$timePeriod = $model->getTimePeriod();
		$export['timePeriodId'] = $timePeriod instanceof TimePeriod ? $timePeriod->getId() : null;

		$command = $model->getServiceNotificationCommand();
		$export['serviceNotificationCommandId'] = $command instanceof Command ? $command->getId() : null;

		$user = $model->getUser();
		$export['userId'] = $user instanceof User ? $user->getId() : null;

		return $export;
	}

	public function findByCommand(Command $command) {
		return $this->findBy(array('serviceNotificationCommandId' => $command->getId()));
	}

	public function findByTimePeriod(TimePeriod $timePeriod) {
		return $this->findBy(array('timePeriodId' => $timePeriod->getId()));
	}

	public function delete($object) {
		$this->validateModelClass($object);
		$this->dispatcher->dispatch(Events::BEFORE_CONTACT_DELETE, new ModelEvent($object));
		parent::delete($object);
	}

}
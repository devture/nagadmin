<?php
namespace Devture\Bundle\NagiosBundle\Repository;

use Doctrine\MongoDB\Database;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Devture\Bundle\SharedBundle\Model\BaseModel;
use Devture\Bundle\SharedBundle\Repository\BaseMongoRepository;
use Devture\Bundle\NagiosBundle\Model\Contact;
use Devture\Bundle\NagiosBundle\Model\TimePeriod;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Repository\TimePeriodRepository;
use Devture\Bundle\NagiosBundle\Repository\CommandRepository;
use Devture\Bundle\NagiosBundle\Event\Events;
use Devture\Bundle\NagiosBundle\Event\ModelEvent;

class ContactRepository extends BaseMongoRepository {

	private $dispatcher;
	private $timePeriodRepository;
	private $commandRepository;

	public function __construct(EventDispatcherInterface $dispatcher, TimePeriodRepository $timePeriodRepository, CommandRepository $commandRepository, Database $db) {
		parent::__construct($db);
		$this->dispatcher = $dispatcher;
		$this->timePeriodRepository = $timePeriodRepository;
		$this->commandRepository = $commandRepository;
	}

	protected function getModelClass() {
		return '\\Devture\Bundle\\NagiosBundle\\Model\\Contact';
	}

	protected function getCollectionName() {
		return 'contact';
	}

	/**
	 * Hydrates a model object from the data object.
	 * @param array $data
	 */
	public function createModel(array $data) {
		$model = new Contact($data);

		if (isset($data['timePeriodId'])) {
			$model->setTimePeriod($this->timePeriodRepository->find($data['timePeriodId']));
		}

		if (isset($data['serviceNotificationCommandId'])) {
			$model->setServiceNotificationCommand($this->commandRepository->find($data['serviceNotificationCommandId']));
		}

		return $model;
	}

	public function exportModel(Contact $model) {
		$export = parent::exportModel($model);

		$timePeriod = $model->getTimePeriod();
		$export['timePeriodId'] = $timePeriod instanceof TimePeriod ? $timePeriod->getId() : null;

		$command = $model->getServiceNotificationCommand();
		$export['serviceNotificationCommandId'] = $command instanceof Command ? $command->getId() : null;

		return $export;
	}

	public function findByCommand(Command $command) {
		return $this->findBy(array('serviceNotificationCommandId' => $command->getId()));
	}

	public function findByTimePeriod(TimePeriod $timePeriod) {
		return $this->findBy(array('timePeriodId' => $timePeriod->getId()));
	}

	public function delete(BaseModel $object) {
		$this->validateModelClass($object);
		$this->dispatcher->dispatch(Events::BEFORE_CONTACT_DELETE, new ModelEvent($object));
		parent::delete($object);
	}

}
<?php
namespace Devture\Bundle\NagiosBundle\Repository;

use Doctrine\MongoDB\Database;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Devture\Bundle\SharedBundle\Repository\BaseMongoRepository;
use Devture\Bundle\NagiosBundle\Model\TimePeriod;
use Devture\Bundle\NagiosBundle\Event\Events;
use Devture\Bundle\NagiosBundle\Event\ModelEvent;

class TimePeriodRepository extends BaseMongoRepository {

	private $dispatcher;

	public function __construct(EventDispatcherInterface $dispatcher, Database $db) {
		$this->dispatcher = $dispatcher;
		parent::__construct($db);
	}

	public function getModelClass() {
		return '\\Devture\Bundle\\NagiosBundle\\Model\\TimePeriod';
	}

	public function getCollectionName() {
		return 'time_period';
	}

	public function delete(TimePeriod $object) {
		$this->dispatcher->dispatch(Events::BEFORE_TIME_PERIOD_DELETE, new ModelEvent($object));
		parent::delete($object);
	}

}
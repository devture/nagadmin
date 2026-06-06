<?php
namespace Devture\Bundle\NagiosBundle\Repository;

use MongoDB\Database;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Devture\Component\DBAL\Repository\BaseMongoRepository;
use Devture\Bundle\NagiosBundle\Model\TimePeriod;
use Devture\Bundle\NagiosBundle\Event\Events;
use Devture\Bundle\NagiosBundle\Event\ModelEvent;

/**
 * @extends BaseMongoRepository<\Devture\Bundle\NagiosBundle\Model\TimePeriod>
 */
class TimePeriodRepository extends BaseMongoRepository {

	private $dispatcher;

	public function __construct(EventDispatcherInterface $dispatcher, Database $db) {
		$this->dispatcher = $dispatcher;
		parent::__construct($db);
	}

	protected function getModelClass() {
		return '\\Devture\Bundle\\NagiosBundle\\Model\\TimePeriod';
	}

	protected function getCollectionName() {
		return 'time_period';
	}

	public function delete($object) {
		$this->validateModelClass($object);
		$this->dispatcher->dispatch(new ModelEvent($object), Events::BEFORE_TIME_PERIOD_DELETE);
		parent::delete($object);
	}

}

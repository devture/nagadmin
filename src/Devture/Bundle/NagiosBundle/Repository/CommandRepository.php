<?php
namespace Devture\Bundle\NagiosBundle\Repository;

use Doctrine\MongoDB\Database;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Devture\Bundle\SharedBundle\Repository\BaseMongoRepository;
use Devture\Bundle\NagiosBundle\Model\Command;
use Devture\Bundle\NagiosBundle\Event\Events;
use Devture\Bundle\NagiosBundle\Event\ModelEvent;

class CommandRepository extends BaseMongoRepository {

	private $dispatcher;

	public function __construct(EventDispatcherInterface $dispatcher, Database $db) {
		$this->dispatcher = $dispatcher;
		parent::__construct($db);
	}

	public function getModelClass() {
		return '\\Devture\Bundle\\NagiosBundle\\Model\\Command';
	}

	public function getCollectionName() {
		return 'command';
	}

	public function delete(Command $object) {
		$this->dispatcher->dispatch(Events::BEFORE_COMMAND_DELETE, new ModelEvent($object));
		parent::delete($object);
	}

}
<?php
namespace Devture\Bundle\NagiosBundle\Repository;

use Doctrine\MongoDB\Database;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Devture\Bundle\SharedBundle\Repository\BaseMongoRepository;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Event\Events;
use Devture\Bundle\NagiosBundle\Event\ModelEvent;

class HostRepository extends BaseMongoRepository {

	private $dispatcher;

	public function __construct(EventDispatcherInterface $dispatcher, Database $db) {
		$this->dispatcher = $dispatcher;
		parent::__construct($db);
	}

	public function getModelClass() {
		return '\\Devture\Bundle\\NagiosBundle\\Model\\Host';
	}

	public function getCollectionName() {
		return 'host';
	}

	public function delete(Host $object) {
		$this->dispatcher->dispatch(Events::BEFORE_HOST_DELETE, new ModelEvent($object));
		parent::delete($object);
	}

}
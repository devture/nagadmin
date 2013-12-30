<?php
namespace Devture\Bundle\NagiosBundle\Repository;

use Doctrine\MongoDB\Database;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Devture\Component\DBAL\Repository\BaseMongoRepository;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Event\Events;
use Devture\Bundle\NagiosBundle\Event\ModelEvent;

class HostRepository extends BaseMongoRepository {

	private $dispatcher;

	public function __construct(EventDispatcherInterface $dispatcher, Database $db) {
		$this->dispatcher = $dispatcher;
		parent::__construct($db);
	}

	protected function getModelClass() {
		return '\\Devture\Bundle\\NagiosBundle\\Model\\Host';
	}

	protected function getCollectionName() {
		return 'host';
	}

	public function findByName($name) {
		return $this->findOneBy(array('name' => $name));
	}

	public function getDistinctGroups() {
		$groupsMap = array();
		foreach ($this->findBy(array(), array()) as $host) {
			foreach ($host->getGroups() as $groupName) {
				$groupsMap[$groupName] = true;
			}
		}
		ksort($groupsMap);
		return array_keys($groupsMap);
	}

	public function delete($object) {
		$this->validateModelClass($object);
		$this->dispatcher->dispatch(Events::BEFORE_HOST_DELETE, new ModelEvent($object));
		parent::delete($object);
	}

}
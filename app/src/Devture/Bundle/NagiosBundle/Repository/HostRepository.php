<?php
namespace Devture\Bundle\NagiosBundle\Repository;

use MongoDB\Database;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Devture\Component\DBAL\Repository\BaseMongoRepository;
use Devture\Bundle\NagiosBundle\Model\Host;
use Devture\Bundle\NagiosBundle\Event\Events;
use Devture\Bundle\NagiosBundle\Event\ModelEvent;

/**
 * @extends BaseMongoRepository<\Devture\Bundle\NagiosBundle\Model\Host>
 */
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

	public function ensureIndexes() {
		$collection = $this->db->selectCollection($this->getCollectionName());

		$collection->createIndex(array(
			'name' => 1,
		));
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
		$this->dispatcher->dispatch(new ModelEvent($object), Events::BEFORE_HOST_DELETE);
		parent::delete($object);
	}

}
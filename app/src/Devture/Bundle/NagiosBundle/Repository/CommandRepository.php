<?php
namespace Devture\Bundle\NagiosBundle\Repository;

use MongoDB\Database;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Devture\Component\DBAL\Repository\BaseMongoRepository;
use Devture\Bundle\NagiosBundle\Event\Events;
use Devture\Bundle\NagiosBundle\Event\ModelEvent;

/**
 * @extends BaseMongoRepository<\Devture\Bundle\NagiosBundle\Model\Command>
 */
class CommandRepository extends BaseMongoRepository {

	private $dispatcher;

	public function __construct(EventDispatcherInterface $dispatcher, Database $db) {
		$this->dispatcher = $dispatcher;
		parent::__construct($db);
	}

	protected function getModelClass() {
		return '\\Devture\Bundle\\NagiosBundle\\Model\\Command';
	}

	protected function getCollectionName() {
		return 'command';
	}

	public function ensureIndexes() {
		$collection = $this->db->selectCollection($this->getCollectionName());

		$collection->createIndex(array(
			'type' => 1,
		));
	}

	public function findAllByType($type) {
		return $this->findBy(array('type' => $type), array());
	}

	public function delete($object) {
		$this->validateModelClass($object);
		$this->dispatcher->dispatch(new ModelEvent($object), Events::BEFORE_COMMAND_DELETE);
		parent::delete($object);
	}

}
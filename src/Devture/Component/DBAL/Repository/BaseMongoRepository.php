<?php
namespace Devture\Component\DBAL\Repository;

use MongoDB\Database;
use MongoDB\BSON\ObjectId;
use Devture\Component\DBAL\Model\BaseModel;
use Devture\Component\DBAL\IdGenerator\AutoGenerator;
use Devture\Component\DBAL\Exception\NotFound;

abstract class BaseMongoRepository extends BaseRepository {

	protected $db;

	abstract protected function getCollectionName();

	public function __construct(Database $db) {
		$this->db = $db;
	}

	public function find($id) {
		$stringId = (string) $id;
		if (isset($this->models[$stringId])) {
			return $this->models[$stringId];
		}

		if ($this->isStringMongoId($id)) {
			$id = new ObjectId($id);
		} else if (is_numeric($id)) {
			$id = (int) $id;
		}

		return $this->findOneBy(array('_id' => $id));
	}

	/**
	 * @param array $criteria
	 * @throws NotFound
	 * @return BaseModel
	 */
	public function findOneBy(array $criteria) {
		$data = $this->getDatabaseCollection()->findOne($criteria);
		if ($data === null) {
			throw new NotFound('Missing object of class ' . $this->getModelClass() . ' for: ' . json_encode($criteria));
		}
		return $this->loadModel($data);
	}

	/**
	 * @param array $criteria
	 * @param array $options find() options (e.g. `sort`, `limit`); passed straight to the driver.
	 * @return BaseModel[]
	 */
	public function findBy(array $criteria, array $options = array()) {
		$results = array();
		foreach ($this->getDatabaseCollection()->find($criteria, $options) as $data) {
			$results[] = $this->loadModel($data);
		}
		return $results;
	}

	public function findAll() {
		return $this->findBy(array(), array());
	}

	public function add($entity) {
		$this->validateModelClass($entity);
		if ($entity->getId() === null) {
			$entity->setId($this->getIdGenerator()->generate($entity));
		}
		$exportedObject = $this->exportModel($entity);
		$this->getDatabaseCollection()->insertOne($exportedObject);
	}

	public function update($entity) {
		$this->validateModelClass($entity);
		if ($entity->getId() === null) {
			throw new \LogicException('Cannot update a non-identifiable object.');
		}
		$exportedObject = $this->exportModel($entity);
		$this->getDatabaseCollection()->replaceOne(array('_id' => $entity->getId()), $exportedObject, array('upsert' => true));
	}

	public function delete($entity) {
		$this->validateModelClass($entity);
		if ($entity->getId() === null) {
			throw new \LogicException('Cannot delete a non-identifiable object.');
		}
		$this->getDatabaseCollection()->deleteOne(array('_id' => $entity->getId()));
		unset($this->models[(string) $entity->getId()]);
		$entity->setId(null);
	}

	/**
	 * @return \Devture\Component\DBAL\IdGenerator\GeneratorInterface
	 */
	protected function getIdGenerator() {
		return new AutoGenerator();
	}

	private function isStringMongoId($string) {
		try {
			//ObjectId throws when given a string that is not a valid 24-char hex id.
			return ($string === (string) new ObjectId($string));
		} catch (\MongoDB\Driver\Exception\InvalidArgumentException $e) {
			return false;
		}
	}

	/**
	 * @return \MongoDB\Collection
	 */
	private function getDatabaseCollection() {
		return $this->db->selectCollection($this->getCollectionName());
	}

}

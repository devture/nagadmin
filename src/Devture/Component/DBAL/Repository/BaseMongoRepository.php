<?php
namespace Devture\Component\DBAL\Repository;

use Doctrine\MongoDB\Database;
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
			$id = new \MongoId($id);
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

	public function findBy(array $criteria, array $cursorTransforms = array()) {
		$cursor = $this->getDatabaseCollection()->find($criteria);
		foreach ($cursorTransforms as $methodKey => $value) {
			$cursor->$methodKey($value);
		}

		$results = array();
		foreach ($cursor as $data) {
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
		$this->getDatabaseCollection()->insert($exportedObject, array('safe' => true));
	}

	public function update($entity) {
		$this->validateModelClass($entity);
		if ($entity->getId() === null) {
			throw new \LogicException('Cannot update a non-identifiable object.');
		}
		$exportedObject = $this->exportModel($entity);
		$this->getDatabaseCollection()->save($exportedObject, array('safe' => true));
	}

	public function delete($entity) {
		$this->validateModelClass($entity);
		if ($entity->getId() === null) {
			throw new \LogicException('Cannot delete a non-identifiable object.');
		}
		$this->getDatabaseCollection()->remove(array('_id' => $entity->getId()), array('justOne' => true));
		unset($this->models[(string) $entity->getId()]);
		$entity->setId(null);
	}

	/**
	 * @return Devture\Component\DBAL\IdGenerator\GeneratorInterface
	 */
	protected function getIdGenerator() {
		return new AutoGenerator();
	}

	private function isStringMongoId($string) {
		try {
			//The old mongodb driver creates a new random id when an invalid $string is given.
			return ($string === (string) new \MongoId($string));
		} catch (\MongoException $e) {
			//The new mongodb driver (1.4+) throws an exception when an invalid $string is given.
			return false;
		}
	}

	/**
	 * @return \Doctrine\MongoDB\Collection
	 */
	private function getDatabaseCollection() {
		return $this->db->selectCollection($this->getCollectionName());
	}

}

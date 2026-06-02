<?php
namespace Devture\Component\DBAL\Repository;

use Devture\Component\DBAL\Model\BaseModel;

abstract class BaseRepository implements RepositoryInterface {

	protected $models = array();

	abstract protected function getModelClass();

	/**
	 * Creates a new model from the data the first time it's called.
	 * If called subsequently for the same id, it will return a reference to the first
	 * object, no matter what $data contains this run.
	 *
	 * @see \Devture\Component\DBAL\Repository\RepositoryInterface::createModel()
	 * @param array $data
	 * @return BaseModel
	 */
	public function createModel(array $data) {
		if (!isset($data['_id'])) {
			return $this->hydrateModel($data);
		}

		$id = (string) $data['_id'];
		if (!isset($this->models[$id])) {
			$this->models[$id] = $this->hydrateModel($data);
		}
		return $this->models[$id];
	}

	/**
	 * @see \Devture\Component\DBAL\Repository\RepositoryInterface::createModel()
	 */
	protected function hydrateModel(array $data) {
		$modelClass = $this->getModelClass();
		return new $modelClass($data);
	}

	/**
	 * Exports a model for persisting to the database.
	 *
	 * @param BaseModel $entity
	 * @return array
	 */
	protected function exportModel($entity) {
		if (!($entity instanceof BaseModel)) {
			throw new \LogicException('Cannot export non-BaseModel-derived objects.');
		}
		return $entity->export();
	}

	protected function loadModel(array $data) {
		if (!isset($data['_id'])) {
			throw new \InvalidArgumentException('Missing _id field in data.');
		}
		return $this->createModel($data);
	}

	protected function validateModelClass($entity) {
		if (!is_object($entity)) {
			throw new \LogicException('Refusing to handle non-object when ' . $this->getModelClass() . ' was expected.');
		}

		//We want to allow only instances of the specified model class to be saved,
		//or of a class derived from it. is_a() takes care of that,
		//otherwise we could've just compared get_class($object) to $this->getModelClass().
		if (!is_a($entity, $this->getModelClass())) {
			throw new \LogicException('Refusing to handle object of class ' . get_class($entity) . ' when ' . $this->getModelClass() . ' was expected.');
		}
	}

}

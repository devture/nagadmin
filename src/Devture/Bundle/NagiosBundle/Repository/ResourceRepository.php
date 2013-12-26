<?php
namespace Devture\Bundle\NagiosBundle\Repository;

use Devture\Component\DBAL\Repository\BaseMongoRepository;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Bundle\NagiosBundle\Model\Resource;

class ResourceRepository extends BaseMongoRepository {

	const RESOURCE_SINGLETON_ID = 'resource';

	protected function getModelClass() {
		return '\\Devture\Bundle\\NagiosBundle\\Model\\Resource';
	}

	protected function getCollectionName() {
		return 'resource';
	}

	/**
	 * @return Resource
	 */
	public function getResource() {
		try {
			$model = $this->find(self::RESOURCE_SINGLETON_ID);
		} catch (NotFound $e) {
			$model = $this->createModel(array());
			$model->setId(self::RESOURCE_SINGLETON_ID);
			$this->add($model);
		}
		return $model;
	}

}
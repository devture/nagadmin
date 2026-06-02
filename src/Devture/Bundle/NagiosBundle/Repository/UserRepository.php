<?php
namespace Devture\Bundle\NagiosBundle\Repository;

class UserRepository extends \Devture\Bundle\UserBundle\Repository\MongoDB\UserRepository {

	protected function getModelClass() {
		return '\\Devture\Bundle\\NagiosBundle\\Model\\User';
	}

	/**
	 * Overrides the parent's index creation, which still uses the legacy
	 * `ensureIndex()` API removed by the native mongodb/mongodb driver.
	 */
	public function ensureIndexes() {
		$userCollection = $this->db->selectCollection($this->getCollectionName());
		$userCollection->createIndex(array('username' => 1), array('unique' => true));

		//This field should be "unique, unless NULL".
		//Because MongoDB's unique indexes do not work that way, do not specify it as unique.
		$userCollection->createIndex(array('email' => 1));
	}

}
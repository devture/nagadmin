<?php
namespace Devture\Bundle\UserBundle\Repository\MongoDB;

use Devture\Component\DBAL\Repository\BaseMongoRepository;
use Devture\Bundle\UserBundle\Repository\UserRepositoryInterface;

/**
 * @extends BaseMongoRepository<\Devture\Bundle\UserBundle\Model\User>
 */
class UserRepository extends BaseMongoRepository implements UserRepositoryInterface {

	protected function getModelClass() {
		return '\\Devture\Bundle\\UserBundle\\Model\\User';
	}

	protected function getCollectionName() {
		return 'devture_user';
	}

	/**
	 * @param \Devture\Bundle\UserBundle\Model\User $model
	 * @return array<string, mixed>
	 */
	protected function exportModel($model) {
		$data = parent::exportModel($model);
		$data['email'] = ($model->getEmail() ? $model->getEmail() : null);
		return $data;
	}

	public function ensureIndexes(): void {
		$userCollection = $this->db->selectCollection($this->getCollectionName());
		$userCollection->createIndex(array('username' => 1), array('unique' => true));

		//This field should be "unique, unless NULL".
		//Because MongoDB's unique indexes do not work that way, do not specify it as unique.
		$userCollection->createIndex(array('email' => 1));
	}

	/**
	 * @param string $username
	 * @return \Devture\Bundle\UserBundle\Model\User
	 */
	public function findByUsername($username) {
		return $this->findOneBy(array('username' => $username));
	}

	/**
	 * @param string $email
	 * @return \Devture\Bundle\UserBundle\Model\User
	 */
	public function findByEmail($email) {
		return $this->findOneBy(array('email' => $email));
	}

}

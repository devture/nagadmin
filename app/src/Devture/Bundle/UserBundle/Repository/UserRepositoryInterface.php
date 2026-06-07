<?php
namespace Devture\Bundle\UserBundle\Repository;

use Devture\Component\DBAL\Repository\RepositoryInterface;

interface UserRepositoryInterface extends RepositoryInterface {

	/**
	 * @param string $username
	 * @return \Devture\Bundle\UserBundle\Model\User
	 */
	public function findByUsername($username);

	/**
	 * @param string $email
	 * @return \Devture\Bundle\UserBundle\Model\User
	 */
	public function findByEmail($email);

	/**
	 * @return void
	 */
	public function ensureIndexes();

}

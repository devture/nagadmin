<?php
namespace Devture\Bundle\UserBundle\Repository;

use Devture\Component\DBAL\Repository\RepositoryInterface;

interface UserRepositoryInterface extends RepositoryInterface {

	public function findByUsername($username);

	public function findByEmail($email);

	public function ensureIndexes();

}

<?php
namespace Devture\Bundle\NagiosBundle\Repository;

class UserRepository extends \Devture\Bundle\UserBundle\Repository\MongoDB\UserRepository {

	protected function getModelClass() {
		return '\\Devture\Bundle\\NagiosBundle\\Model\\User';
	}

}
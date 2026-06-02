<?php
namespace Devture\Component\DBAL\IdGenerator;

use Devture\Component\DBAL\Model\BaseModel;

class AutoGenerator implements GeneratorInterface {

	public function generate(BaseModel $entity) {
		return new \MongoId();
	}

}

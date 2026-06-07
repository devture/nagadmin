<?php
namespace Devture\Component\DBAL\IdGenerator;

use Devture\Component\DBAL\Model\BaseModel;

interface GeneratorInterface {

	/**
	 * @return mixed
	 */
	public function generate(BaseModel $entity);

}

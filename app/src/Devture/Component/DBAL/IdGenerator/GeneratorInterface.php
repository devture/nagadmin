<?php
namespace Devture\Component\DBAL\IdGenerator;

use Devture\Component\DBAL\Model\BaseModel;

interface GeneratorInterface {

	public function generate(BaseModel $entity);

}

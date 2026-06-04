<?php
namespace Devture\Bundle\NagiosBundle\Event;

use Devture\Component\DBAL\Model\BaseModel;
use Symfony\Contracts\EventDispatcher\Event;

class ModelEvent extends Event {

	private $model;

	public function __construct(BaseModel $model) {
		$this->model = $model;
	}

	public function getModel() {
		return $this->model;
	}

}
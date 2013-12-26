<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Component\DBAL\Model\BaseModel;

class TimePeriodRule extends BaseModel {

	public function setDateRange($value) {
		$this->setAttribute('dateRange', $value);
	}

	public function getDateRange() {
		return $this->getAttribute('dateRange');
	}

	public function setTimeRange($value) {
		$this->setAttribute('timeRange', $value);
	}

	public function getTimeRange() {
		return $this->getAttribute('timeRange');
	}

}
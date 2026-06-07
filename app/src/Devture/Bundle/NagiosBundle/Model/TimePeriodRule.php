<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Component\DBAL\Model\BaseModel;

class TimePeriodRule extends BaseModel {

	/**
	 * @param string $value
	 * @return void
	 */
	public function setDateRange($value) {
		$this->setAttribute('dateRange', $value);
	}

	/**
	 * @return string
	 */
	public function getDateRange() {
		return $this->getAttribute('dateRange');
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function setTimeRange($value) {
		$this->setAttribute('timeRange', $value);
	}

	/**
	 * @return string
	 */
	public function getTimeRange() {
		return $this->getAttribute('timeRange');
	}

}

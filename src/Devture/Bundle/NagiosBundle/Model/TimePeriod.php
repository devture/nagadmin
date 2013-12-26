<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Component\DBAL\Model\BaseModel;

class TimePeriod extends BaseModel {

	public function setName($value) {
		$this->setAttribute('name', $value);
	}

	public function getName() {
		return $this->getAttribute('name');
	}

	public function setTitle($value) {
		$this->setAttribute('title', $value);
	}

	public function getTitle() {
		return $this->getAttribute('title');
	}

	public function clearRules() {
		$this->setAttribute('rules', array());
	}

	public function addRule(TimePeriodRule $rule) {
		$rules = $this->getRulesRaw();
		$rules[] = $rule->export();
		$this->setAttribute('rules', $rules);
	}

	private function getRulesRaw() {
		return $this->getAttribute('rules', array());
	}

	public function getRules() {
		$rules = array();
		foreach ($this->getRulesRaw() as $ruleData) {
			$rules[] = new TimePeriodRule($ruleData);
		}
		return $rules;
	}

}
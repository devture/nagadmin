<?php
namespace Devture\Bundle\NagiosBundle\Model;

use Devture\Component\DBAL\Model\BaseModel;

class TimePeriod extends BaseModel {

	/**
	 * @param string $value
	 * @return void
	 */
	public function setName($value) {
		$this->setAttribute('name', $value);
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->getAttribute('name');
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function setTitle($value) {
		$this->setAttribute('title', $value);
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->getAttribute('title');
	}

	public function clearRules(): void {
		$this->setAttribute('rules', array());
	}

	public function addRule(TimePeriodRule $rule): void {
		$rules = $this->getRulesRaw();
		$rules[] = $rule->export();
		$this->setAttribute('rules', $rules);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function getRulesRaw() {
		return $this->getAttribute('rules', array());
	}

	/**
	 * @return list<TimePeriodRule>
	 */
	public function getRules() {
		$rules = array();
		foreach ($this->getRulesRaw() as $ruleData) {
			$rules[] = new TimePeriodRule($ruleData);
		}
		return $rules;
	}

}

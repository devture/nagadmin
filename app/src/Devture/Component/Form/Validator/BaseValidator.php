<?php
namespace Devture\Component\Form\Validator;

abstract class BaseValidator implements ValidatorInterface {

	public function validate($object, array $options = array()) {
		return new ViolationsList();
	}

	protected function isEmpty($value) {
		return in_array($value, array('', null), true);
	}

}

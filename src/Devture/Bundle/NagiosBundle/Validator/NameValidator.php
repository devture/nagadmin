<?php
namespace Devture\Bundle\NagiosBundle\Validator;

class NameValidator {

	static public function isValid($name) {
		return preg_match("/^[a-z][a-z0-9_\-\.]+$/", $name);
	}

}
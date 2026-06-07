<?php
namespace Devture\Bundle\NagiosBundle\Validator;

class NameValidator {

	/**
	 * @param string $name
	 */
	static public function isValid($name): bool {
		return (bool) preg_match("/^[a-z][a-z0-9_\-\.]+$/", $name);
	}

}

<?php
namespace Devture\Component\Form\Validator;

interface ValidatorInterface {

	/**
	 * @param object $object
	 * @param array<string, mixed> $options
	 * @return ViolationsList
	 */
	public function validate($object, array $options = array());

}

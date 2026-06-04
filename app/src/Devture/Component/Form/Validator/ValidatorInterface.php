<?php
namespace Devture\Component\Form\Validator;

interface ValidatorInterface {

	/**
	 * @param object $object
	 * @param array $options
	 * @return ViolationsList
	 */
	public function validate($object, array $options = array());

}

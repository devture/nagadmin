<?php
namespace Devture\Component\Form\Token;

interface TokenManagerInterface {

	/**
	 * @param string $salt
	 */
	public function setSalt($salt);

	/**
	 * @param string $intention
	 */
	public function generate($intention);

	/**
	 * @param string $intention
	 * @param string $token
	 * @return boolean
	 */
	public function isValid($intention, $token);

}

<?php
namespace Devture\Component\Form\Binder;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\Form\Validator\ViolationsList;
use Devture\Component\Form\Validator\ValidatorInterface;
use Devture\Component\Form\Token\TokenManagerInterface;

interface BinderInterface {

	/**
	 * The violations that occurred during the last `bind()` call.
	 *
	 * @return ViolationsList
	 */
	public function getViolations();

	/**
	 * @param ValidatorInterface $validator
	 * @return void
	 */
	public function setValidator(?ValidatorInterface $validator = null);

	/**
	 * @return ValidatorInterface|NULL
	 */
	public function getValidator();

	/**
	 * @param TokenManagerInterface $tokenManager
	 * @param string $intention
	 * @return void
	 */
	public function setCsrfProtection(?TokenManagerInterface $tokenManager = null, $intention = null);

	/**
	 * @return TokenManagerInterface|NULL
	 */
	public function getCsrfTokenManager();

	/**
	 * @return string|NULL
	 */
	public function getCsrfIntention();

	/**
	 * @return string
	 */
	public function getCsrfTokenFieldName();

	/**
	 * Binds the request parameters to the provided entity.
	 * Returns false if any violations occur during binding or validation.
	 *
	 * @param mixed $entity
	 * @param Request $request
	 * @param array<string, mixed> $options
	 * @return boolean - whether binding (and subsequent validation, if enabled) were successful
	 */
	public function bind($entity, Request $request, array $options = array());

}

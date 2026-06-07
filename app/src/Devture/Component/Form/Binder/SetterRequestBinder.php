<?php
namespace Devture\Component\Form\Binder;

use Symfony\Component\HttpFoundation\Request;
use Devture\Component\Form\Validator\ViolationsList;
use Devture\Component\Form\Validator\ValidatorInterface;
use Devture\Component\Form\Token\TokenManagerInterface;

abstract class SetterRequestBinder implements BinderInterface {

	private ?ValidatorInterface $validator;
	private ViolationsList $violations;
	private ?TokenManagerInterface $csrfTokenManager = null;
	private ?string $csrfIntention = null;

	/**
	 * @param mixed $entity
	 * @param array<string, mixed> $options
	 * @return void
	 */
	abstract protected function doBindRequest($entity, Request $request, array $options = array());

	public function __construct(?ValidatorInterface $validator = null) {
		$this->validator = $validator;
		$this->violations = new ViolationsList();
	}

	public function getViolations() {
		return $this->violations;
	}

	public function setValidator(?ValidatorInterface $validator = null) {
		$this->validator = $validator;
	}

	public function getValidator() {
		return $this->validator;
	}

	public function setCsrfProtection(?TokenManagerInterface $tokenManager = null, $intention = null) {
		$this->csrfTokenManager = $tokenManager;
		$this->csrfIntention = $intention;
	}

	public function getCsrfTokenManager() {
		return $this->csrfTokenManager;
	}

	public function getCsrfIntention() {
		return $this->csrfIntention;
	}

	public function getCsrfTokenFieldName() {
		return '_csrf_token';
	}

	public function bind($entity, Request $request, array $options = array()) {
		$this->violations = new ViolationsList();

		if ($this->csrfTokenManager instanceof TokenManagerInterface) {
			$token = $request->request->get($this->getCsrfTokenFieldName());
			$token = is_string($token) ? $token : '';
			if (!$this->csrfTokenManager->isValid((string) $this->csrfIntention, $token)) {
				$this->violations->add('__other__', 'Cannot confirm your identity. Reload and try again.');
				return false;
			}
		}

		$this->doBindRequest($entity, $request, $options);

		if ($this->validator instanceof ValidatorInterface) {
			$this->violations->merge($this->validator->validate($entity, $options));
		}

		return count($this->violations) === 0;
	}

	/**
	 * @param mixed $entity
	 * @param array<string, mixed> $values
	 * @return void
	 */
	protected function bindAll($entity, array $values) {
		foreach ($values as $key => $value) {
			$setter = 'set' . ucfirst($key);
			if (method_exists($entity, $setter)) {
				$entity->$setter($value);
			}
		}
	}

	/**
	 * @param mixed $entity
	 * @param array<string, mixed> $values
	 * @param list<string> $keysWhitelisted
	 * @return void
	 */
	protected function bindWhitelisted($entity, array $values, array $keysWhitelisted) {
		$valuesAllowed = array();
		foreach ($values as $key => $value) {
			if (in_array($key, $keysWhitelisted)) {
				$valuesAllowed[$key] = $value;
			}
		}
		$this->bindAll($entity, $valuesAllowed);
	}

}

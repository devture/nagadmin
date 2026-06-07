<?php
namespace Devture\Component\Form\Validator;

/**
 * @implements \IteratorAggregate<string, list<array{message: string, params: array<string, mixed>}>>
 */
class ViolationsList implements \IteratorAggregate, \Countable {

	/**
	 * @var array<string, list<array{message: string, params: array<string, mixed>}>>
	 */
	private array $violations = array();

	/**
	 * @param array<string, mixed> $params
	 * @return void
	 */
	public function add(string $key, string $message, array $params = array()) {
		$this->violations[$key][] = array('message' => $message, 'params' => $params);
	}

	/**
	 * @return list<array{message: string, params: array<string, mixed>}>
	 */
	public function get(string $key): array {
		if (!isset($this->violations[$key])) {
			return array();
		}
		return $this->violations[$key];
	}

	public function merge(ViolationsList $other): void {
		foreach ($other->violations as $key => $items) {
			foreach ($items as $item) {
				$this->violations[$key][] = $item;
			}
		}
	}

	public function getIterator(): \Iterator {
		return new \ArrayIterator($this->violations);
	}

	public function count(): int {
		return count($this->violations);
	}

}

<?php
namespace Devture\Bundle\NagiosBundle\Helper;

class Colorizer {

	/**
	 * @var list<string>
	 */
	private array $colors;

	/**
	 * @param list<string> $colors
	 */
	public function __construct(array $colors) {
		$this->colors = $colors;
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	public function colorize($value) {
		$value = (string) $value;

		$sum = hexdec(substr(hash('crc32', $value), 0, 2));

		$idx = $sum % count($this->colors);

		return $this->colors[$idx];
	}

}

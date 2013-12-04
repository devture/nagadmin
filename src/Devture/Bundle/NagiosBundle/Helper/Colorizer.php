<?php
namespace Devture\Bundle\NagiosBundle\Helper;

class Colorizer {

	private $colors;

	public function __construct(array $colors) {
		$this->colors = $colors;
	}

	public function colorize($value) {
		$value = (string)$value;

		$sum = hexdec(substr(hash('crc32', $value), 0, 2));

		$idx = $sum % count($this->colors);

		return $this->colors[$idx];
	}

}
<?php
namespace Devture\Bundle\NagiosBundle\Twig;

class ColorizeExtension extends \Twig_Extension {

	private $colors = array('#014de7', '#3a87ad', '#06cf99', '#8fcf06', '#dda808', '#e76d01', '#7801e7', '#353535', '#888888',);

	public function getName() {
		return 'devture_nagios.colorize_extension';
	}

	public function getFilters() {
		return array(
			'colorize' => new \Twig_Filter_Method($this, 'colorize'),
		);
	}

	public function colorize($value) {
		$value = (string)$value;

		$sum = hexdec(substr(hash('crc32', $value), 0, 2));

		$idx = $sum % count($this->colors);

		return $this->colors[$idx];
	}

}


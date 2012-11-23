<?php
namespace Devture\Website\Twig\Extension;

class StaticFileStamperExtension extends \Twig_Extension {

	protected $basePath;
	protected $baseUri;

	public function __construct($basePath, $baseUri = '') {
		$this->basePath = rtrim($basePath, '/');
		$this->baseUri = $baseUri;
	}

	public function getName() {
		return 'static_file_stamper';
	}

	public function getFunctions() {
		return array(
			'static_url' => new \Twig_Function_Method($this, 'getStaticUrl'),
		);
	}

	public function getStaticUrl($relativePath) {
		$filePath = $this->basePath . '/' . ltrim($relativePath, '/');
		if (file_exists($filePath)) {
			$timestamp = filemtime($filePath);
			return $this->baseUri . $relativePath . '?' . $timestamp;
		}
		return $this->baseUri . $relativePath;
	}

}

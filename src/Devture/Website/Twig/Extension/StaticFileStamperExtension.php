<?php
namespace Devture\Website\Twig\Extension;

class StaticFileStamperExtension extends \Twig\Extension\AbstractExtension {

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
			new \Twig\TwigFunction('static_url', [$this, 'getStaticUrl']),
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

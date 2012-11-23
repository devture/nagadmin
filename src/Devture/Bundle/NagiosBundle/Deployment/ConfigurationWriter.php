<?php
namespace Devture\Bundle\NagiosBundle\Deployment;

use Symfony\Component\Finder\Finder;

class ConfigurationWriter {

	public function cleanup($path) {
		foreach (Finder::create()->files()->in($path) as $file) {
			unlink($file->getRealPath());
		}

		foreach (iterator_to_array(Finder::create()->directories()->in($path)) as $dir) {
			rmdir($dir->getRealPath());
		}
	}

	/**
	 * @param string $path
	 * @param array $configurationFiles
	 */
	public function write($path, array $configurationFiles) {
		$path = rtrim($path, '/');

		if (!file_exists($path)) {
			throw new \LogicException('Refusing to write to non-existing path: ' . $path);
		}

		foreach ($configurationFiles as $configurationFile) {
			$filePath = $path . '/' . $configurationFile->getPath();

			//The file may belong to a subdirectory. Make sure to create it, before writing.
			$fileDir = dirname($filePath);
			if (!file_exists($fileDir)) {
				mkdir($fileDir, 0777, true);
			}

			file_put_contents($filePath, $configurationFile->getConfiguration());
		}
	}

}
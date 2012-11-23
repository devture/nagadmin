<?php
namespace Devture\Bundle\NagiosBundle\Deployment\Handler;

use Symfony\Component\Process\Process;
use Devture\Bundle\NagiosBundle\Deployment\ConfigurationWriter;
use Devture\Bundle\NagiosBundle\Exception\DeploymentFailedException;

class DeploymentHandler implements DeploymentHandlerInterface {

	private $writer;
	private $path;
	private $postDeploymentCmd;

	public function __construct(ConfigurationWriter $writer, $path, $postDeploymentCmd) {
		$this->writer = $writer;
		$this->path = $path;
		$this->postDeploymentCmd = $postDeploymentCmd;
	}

	/**
	 * @param array $configurationFiles
	 * @throws DeploymentFailedException
	 */
	public function deploy(array $configurationFiles) {
		if (!file_exists($this->path)) {
			throw new DeploymentFailedException('Cannot deploy to non-existent path `' . $this->path . '`');
		}

		if (!is_writable($this->path)) {
			throw new DeploymentFailedException('Cannot deploy to non-writable path `' . $this->path . '`');
		}

		$this->writer->cleanup($this->path);

		$this->writer->write($this->path, $configurationFiles);

		try {
			$process = new Process($this->postDeploymentCmd . ' 2>&1');
			$process->setTimeout(60);
			$process->run();
			if (!$process->isSuccessful()) {
				throw new \RuntimeException($process->getOutput());
			}
		} catch (\RuntimeException $e) {
			throw new DeploymentFailedException($e->getMessage(), null, $e);
		}
	}

}

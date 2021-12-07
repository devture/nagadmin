<?php
namespace Devture\Bundle\NagiosBundle\Deployment\Handler;

use Devture\Bundle\NagiosBundle\Deployment\ConfigurationWriter;
use Devture\Bundle\NagiosBundle\Exception\DeploymentFailedException;
use Devture\Bundle\NagiosBundle\NagiosCommand\Submitter;

class DeploymentHandler implements DeploymentHandlerInterface {

	private $writer;
	private $path;
	private $submitter;

	public function __construct(ConfigurationWriter $writer, $path, Submitter $submitter) {
		$this->writer = $writer;
		$this->path = $path;
		$this->submitter = $submitter;
	}

	/**
	 * @param array $configurationFiles
	 * @throws DeploymentFailedException
	 */
	public function deploy(array $configurationFiles, bool $reloadNagios): void {
		if (!file_exists($this->path)) {
			throw new DeploymentFailedException('Cannot deploy to non-existent path `' . $this->path . '`');
		}

		if (!is_writable($this->path)) {
			throw new DeploymentFailedException('Cannot deploy to non-writable path `' . $this->path . '`');
		}

		$this->writer->cleanup($this->path);

		$this->writer->write($this->path, $configurationFiles);

		if ($reloadNagios) {
			$command = sprintf('[%d] SHUTDOWN_PROGRAM', time());
			$this->submitter->submit($command);
		}
	}

}

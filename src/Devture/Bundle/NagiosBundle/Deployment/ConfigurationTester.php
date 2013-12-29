<?php
namespace Devture\Bundle\NagiosBundle\Deployment;

use Symfony\Component\Process\Process;
use Devture\Bundle\NagiosBundle\Deployment\ConfigurationFile;

class ConfigurationTester {

	private $writer;
	private $mainFileTemplatePath;

	public function __construct(ConfigurationWriter $writer, $mainFileTemplatePath) {
		$this->writer = $writer;
		$this->mainFileTemplatePath = $mainFileTemplatePath;
	}

	public function test(array $configurationFiles) {
		$path = rtrim(sys_get_temp_dir(), '/') . '/' . uniqid('nagadmin-test');

		mkdir($path);

		$checkResultPath = $path . '/checkresults';
		mkdir($checkResultPath, 0777);

		$logFilePath = '/dev/null';

		$configurationFiles[] = $this->createMainConfigFile($path, $configurationFiles, $checkResultPath, $logFilePath);

		$this->writer->write($path, $configurationFiles);

		try {
			$process = new Process('nagios --verify-config ' . escapeshellarg($path . '/nagios.cfg') . ' 2>&1');
			$process->setTimeout(10);
			$process->run();
			if (!$process->isSuccessful()) {
				throw new \RuntimeException($process->getOutput());
			}
			$isValid = true;
			$checkOutput = $process->getOutput();
		} catch (\RuntimeException $e) {
			$isValid = false;
			$checkOutput = $e->getMessage();
		}

		$this->writer->cleanup($path);
		rmdir($path);

		return array($isValid, $checkOutput);
	}

	private function createMainConfigFile($path, array $configurationFiles, $checkResultPath, $logFilePath) {
		$mainConfigFile = new ConfigurationFile('nagios.cfg', ConfigurationFile::TYPE_CONFIGURATION_FILE);

		$mainConfigFile->addVariable('check_result_path', $checkResultPath);
		$mainConfigFile->addVariable('log_file', $logFilePath);
		$mainConfigFile->addVariable('illegal_macro_output_chars', '`~$&|\'"<>');

		foreach ($configurationFiles as $configurationFile) {
			$type = $configurationFile->getType();
			if ($type === ConfigurationFile::TYPE_CONFIGURATION_FILE) {
				$mainConfigFile->addVariable('cfg_file', $path . '/' . $configurationFile->getPath());
			} else if ($type === ConfigurationFile::TYPE_RESOURCE_FILE) {
				$mainConfigFile->addVariable('resource_file', $path . '/' . $configurationFile->getPath());
			}
		}

		return $mainConfigFile;
	}

}
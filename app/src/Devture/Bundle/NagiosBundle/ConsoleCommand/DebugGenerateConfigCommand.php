<?php

namespace Devture\Bundle\NagiosBundle\ConsoleCommand;

use Devture\Bundle\NagiosBundle\Deployment\ConfigurationCollector;
use Devture\Bundle\NagiosBundle\Deployment\ConfigurationWriter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
	name: 'nagadmin:debug:generate-config',
	description: 'Generates the Nagios configuration from the database into the given directory (no deploy/reload).',
)]
class DebugGenerateConfigCommand extends Command
{
	public function __construct(
		private readonly ConfigurationCollector $collector,
		private readonly ConfigurationWriter $writer,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->addArgument('output-dir', InputArgument::REQUIRED, 'Directory to write the generated configuration into.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);

		$outputDir = rtrim($input->getArgument('output-dir'), '/');
		if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
			$io->error(sprintf('Could not create output directory: %s', $outputDir));

			return Command::FAILURE;
		}

		$files = $this->collector->collect();
		$this->writer->write($outputDir, $files);

		$io->success(sprintf('Wrote %d configuration file(s) to %s', count($files), $outputDir));

		return Command::SUCCESS;
	}
}

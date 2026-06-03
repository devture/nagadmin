<?php

namespace Devture\Bundle\NagiosBundle\ConsoleCommand;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'nagadmin:debug:mongodb',
    description: 'Shows the configured MongoDB database and its collection document counts (connectivity check).',
)]
class DebugMongoDbCommand extends Command
{
    public function __construct(private readonly \MongoDB\Database $database)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->writeln(sprintf('Database: <info>%s</info>', $this->database->getDatabaseName()));

        $rows = [];
        foreach ($this->database->listCollectionNames() as $name) {
            $rows[] = [$name, $this->database->selectCollection($name)->countDocuments([])];
        }

        $io->table(['Collection', 'Documents'], $rows);

        return Command::SUCCESS;
    }
}

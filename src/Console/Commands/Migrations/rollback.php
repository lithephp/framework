<?php

use Lithe\Support\Env;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Command\Command;
use Lithe\Console\Line;

// Create the command
return Line::create(
    'migrate:rollback', // Command name
    'Rollback the last database migration', // Command description
    function (InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);

        try {
            // Initialize the Migration class with a PDO connection
            Migration::init(\Lithe\Database\Manager::initialize('pdo'));
        } catch (Exception $e) {
            // Output an error message if initialization fails
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        // Create a custom style for the "INFO" message with a blue background
        $infoStyle = new OutputFormatterStyle('white', 'blue');
        $output->getFormatter()->setStyle('info-bg', $infoStyle);

        // Retrieve the options from the input
        $batchOption = $input->getOption('batch');
        $forceOption = $input->getOption('force');

        // Check if the force option is enabled and if in production
        if ($forceOption && $this->isProduction()) {
            $io->warning('You are in production mode. Using the --force option is risky.');
            if (!$io->confirm('Do you really want to proceed?', false)) {
                return Command::SUCCESS;
            }
        }

        // Find the batch to rollback to
        $batch = $batchOption ?? Migration::findLastBatch();

        if ($batch === false) {
            $io->writeln("\n\r<info-bg> INFO </info-bg> No migrations found.\n");
            return Command::SUCCESS;
        }

        $io->writeln("\n\r<info-bg> INFO </info-bg> Rolling back migrations up to batch $batch.\n");

        // Retrieve the migrations to rollback
        $migrations = Migration::getWhere('batch', $batch);
        $executedAnyRollback = false;

        // Rollback each migration
        foreach ($migrations as $migration) {
            $executedAnyRollback = rollbackMigration($migration, $io) || $executedAnyRollback;
        }

        // Notify if no migrations were rolled back
        if (!$executedAnyRollback) {
            $io->writeln("\n\r<info-bg> INFO </info-bg> No migrations to rollback.\n");
        } else {
            $io->newLine();
        }

        return Command::SUCCESS;
    },
    [],
    [
        'batch' => ['b', InputOption::VALUE_OPTIONAL, 'Rollback migrations up to a specific batch number'],
        'force' => ['f', InputOption::VALUE_NONE, 'Force the operation to run when in production']
    ]
);

// Define the isProduction method in the scope of the anonymous function
function isProduction(): bool
{
    return Env::get('APP_PRODUCTION_MODE', false);
}

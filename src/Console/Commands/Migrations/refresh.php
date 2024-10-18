<?php

use Lithe\Console\Line;
use Lithe\Database\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

// Define the command
return Line::create(
    'migrate:refresh', // Command name
    'Rollback all database migrations and run them again', // Command description
    function (InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);

        try {
            // Initialize the Migration class with a PDO connection
            Migration::init(\Lithe\Database\Manager::initialize('pdo'));
        } catch (Exception $e) {
            // Output error message if initialization fails
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        // Create a custom style for the "INFO" message with a blue background
        $infoStyle = new OutputFormatterStyle('white', 'blue');
        $output->getFormatter()->setStyle('info-bg', $infoStyle);

        // Retrieve all migrations in descending order
        $migrations = Migration::getOrdered('id', 'DESC');
        $executedAnyRollback = false;

        // Check if the migrations directory exists
        if (is_dir(PROJECT_ROOT . '/database/migrations')) {
            // If there are no migrations to rollback
            if (empty($migrations)) {
                $io->writeln("\n\r<info-bg> INFO </info-bg> Nothing to rollback.\n");
                return Command::SUCCESS;
            } else {
                // Notify that rollback of migrations is starting
                $io->writeln("\n\r<info-bg> INFO </info-bg> Rolling back all migrations.\n");

                // Rollback each migration
                foreach ($migrations as $migration) {
                    $executedAnyRollback = rollbackMigration($migration, $io) || $executedAnyRollback;
                }
            }
        } else {
            // If the migrations directory does not exist, output an error
            $io->writeln("\n\r<info-bg> INFO </info-bg> Migrations directory not found.\n");
            return Command::FAILURE;
        }

        // If no rollback was executed, notify the user
        if (!$executedAnyRollback) {
            $io->writeln("\n\r<info-bg> INFO </info-bg> Nothing to rollback.\n");
            return Command::SUCCESS;
        }

        // Run the migrations again
        runMigrations($io);

        return Command::SUCCESS;
    },
    [], // No arguments
    []  // No options
);

/**
 * Runs all the database migrations.
 *
 * @param SymfonyStyle $io The SymfonyStyle for console output.
 */
function runMigrations(SymfonyStyle $io): void
{
    $dir = PROJECT_ROOT . '/database/migrations';
    $files = array_diff(scandir($dir), array('.', '..'));
    $batch = 1;

    if (count($files) === 0) {
        $io->writeln("\n\r<info-bg> INFO </info-bg> No migrations to run.\n");
        return;
    }

    $io->writeln("\n\r<info-bg> INFO </info-bg> Running migrations.\n");

    foreach ($files as $file) {
        $path = "$dir/$file";
        $migrationClass = include $path;

        if (!is_object($migrationClass)) {
            $io->writeln("<error>Failed to load migration class from '$path'.</error>");
            continue;
        }

        $migrationClass->up(DB::connection());

        Migration::add($path, $batch);

        $io->writeln(sprintf("\r %s .......................................................................................... <info>DONE</info>", basename($path)));
    }

    $io->newLine();
}

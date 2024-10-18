<?php

use Lithe\Console\Line;
use Lithe\Database\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

return Line::create(
    'migrate',
    'Run the database migrations',
    function (InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);

        try {
            Migration::init(\Lithe\Database\Manager::initialize('pdo'));

            // Create a custom style for the "INFO" message
            $outputStyle = new OutputFormatterStyle('white', 'blue');
            $output->getFormatter()->setStyle('info-bg', $outputStyle);

            // Ensure the migrations table exists
            Migration::createTableIfItDoesntExist();

            $migrationsDir = PROJECT_ROOT . '/database/migrations';

            if (is_dir($migrationsDir)) {
                $migrations = Migration::all();
                $executedAnyMigration = false;
                $lastBatch = Migration::findLastBatch();
                $currentBatch = $lastBatch ? $lastBatch + 1 : 1;

                // Get all migration files, excluding '.' and '..'
                $migrationFiles = array_diff(scandir($migrationsDir), ['.', '..']);

                if (count($migrationFiles) === 0) {
                    $io->writeln("\n\r<info-bg> INFO </info-bg> Nothing to migrate.\n");
                    return Command::SUCCESS;
                }

                $io->writeln("\n\r<info-bg> INFO </info-bg> Running migrations.\n");

                foreach ($migrationFiles as $file) {
                    $filePath = "$migrationsDir/$file";

                    if (shouldMigrate($filePath, $migrations)) {
                        $executedAnyMigration = executeMigration($filePath, $currentBatch, $io);
                    }
                }

                if (!$executedAnyMigration) {
                    $io->writeln("\n\r<info-bg> INFO </info-bg> All migrations are up to date.\n");
                }

                $io->newLine();
            } else {
                $io->writeln("\n\r<info-bg> INFO </info-bg> Migrations directory not found.\n");
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    },
    [], // No arguments
    []  // No options
);

/**
 * Determine if a migration should be executed.
 *
 * @param string $filePath The path to the migration file.
 * @param \Illuminate\Support\Collection $migrations The collection of executed migrations.
 * @return bool True if the migration should be executed, False otherwise.
 */
function shouldMigrate(string $filePath, $migrations): bool
{
    foreach ($migrations as $migration) {
        if ($migration['migration'] === $filePath) {
            return false;
        }
    }
    return true;
}


/**
 * Execute a migration.
 *
 * @param string $filePath The path to the migration file.
 * @param int $batch The batch number for the migration.
 * @param SymfonyStyle $io The SymfonyStyle output instance.
 * @return bool True if the migration was executed successfully, False otherwise.
 */
function executeMigration(string $filePath, int $batch, SymfonyStyle $io): bool
{
    $migrationClass = include $filePath;

    if (!is_object($migrationClass)) {
        $io->writeln("<error>Failed to load migration class from '$filePath'.</error>");
        return false;
    }

    $migrationClass->up(DB::connection());

    Migration::add($filePath, $batch);

    $io->writeln(sprintf("\r %s .......................................................................................... <info>DONE</info>", basename($filePath)));

    return true;
}

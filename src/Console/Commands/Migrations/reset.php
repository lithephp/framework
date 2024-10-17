<?php

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Command\Command;
use Lithe\Console\Line;

// Define the command
return Line::create(
    'migrate:reset', // Command name
    'Rollback all database migrations', // Command description
    function (InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);

        try {
            // Initialize the Migration class with PDO connection
            Migration::init(\Lithe\Database\Manager::initialize('pdo', true));
        } catch (Exception $e) {
            // Output error message and return failure status
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        // Create a custom style for the "INFO" message with a blue background
        $infoStyle = new OutputFormatterStyle('white', 'blue');
        $output->getFormatter()->setStyle('info-bg', $infoStyle);

        // Retrieve all migrations in descending order
        $migrations = Migration::getOrdered('id', 'DESC');
        $executedAnyRollback = false;

        // Check if there are no migrations to rollback
        if (empty($migrations)) {
            $io->writeln("\n\r<info-bg> INFO </info-bg> Nothing to rollback.\n");
            return Command::SUCCESS;
        }

        $io->writeln("\n\r<info-bg> INFO </info-bg> Rolling back all migrations.\n");

        // Rollback each migration
        foreach ($migrations as $migration) {
            $executedAnyRollback = rollbackMigration($migration, $io) || $executedAnyRollback;
        }

        // If no rollback was executed, inform the user
        if (!$executedAnyRollback) {
            $io->writeln("\n\r<info-bg> INFO </info-bg> Nothing to rollback.\n");
        } else {
            $io->newLine();
        }

        return Command::SUCCESS;
    },
    // Arguments and options can be passed here, if needed
    [],
    []
);

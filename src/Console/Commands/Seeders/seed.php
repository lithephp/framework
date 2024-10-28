<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Lithe\Console\Line;
use Lithe\Database\Manager;

return Line::create(
    'db:seed', // Command name
    'Run the database seeders', // Command description
    function (InputInterface $input, OutputInterface $output) {
        $outputStyle = new OutputFormatterStyle('white', 'blue');
        $output->getFormatter()->setStyle('info-bg', $outputStyle);
        $io = new SymfonyStyle($input, $output);

        // Retrieve the 'class' option from the input
        $class = $input->getOption('class');

        // Define the path where seeder files are stored
        $seedersPath = PROJECT_ROOT . '/database/seeders/';

        // If a class is specified, run that specific seeder
        if ($class) {
            // Check if the seeder class file exists
            $filePath = $seedersPath . $class . '.php';
            if (!file_exists($filePath)) {
                $io->error("Seeder class [$class] does not exist.");
                return Command::FAILURE;
            }

            // Include the seeder class
            require_once $filePath;

            // Create an instance of the seeder class and run it
            $seederInstance = new $class();
            if (method_exists($seederInstance, 'run')) {
                $seederInstance->run(Manager::connection());
                $io->writeln("\n\r<info-bg> INFO </info-bg> Seeder $class executed successfully.\n");
            } else {
                $io->error("Seeder class [$class] must have a run() method.");
                return Command::FAILURE;
            }
        } else {
            // If no class is specified, run all seeders in the directory
            $files = glob($seedersPath . '*.php');

            foreach ($files as $file) {
                // Get the class name from the file name
                $fileName = basename($file, '.php');
                // Include the seeder class
                require_once $file;

                // Create an instance of the seeder class and run it
                if (class_exists($fileName)) {
                    $seederInstance = new $fileName();
                    if (method_exists($seederInstance, 'run')) {
                        $seederInstance->run(Manager::connection());
                        $io->writeln("\n\r<info-bg> INFO </info-bg> Seeder $fileName executed successfully.\n");
                    } else {
                        $io->error("Seeder class [$fileName] must have a run() method.");
                    }
                } else {
                    $io->error("Seeder class [$fileName] does not exist.");
                }
            }
        }

        return Command::SUCCESS;
    },
    [],
    [
        // Define the 'class' option for the command
        'class' => [
            null,
            InputOption::VALUE_REQUIRED,
            'The seeder class to execute',
        ],
    ]
);

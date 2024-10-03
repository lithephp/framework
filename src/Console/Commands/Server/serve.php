<?php

use Lithe\Console\Line;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// Define the command
return Line::create(
    'serve', // Command name
    'Starts the local development server', // Command description
    function (InputInterface $input, OutputInterface $output) {
        $io = new SymfonyStyle($input, $output);

        // Create a custom style for informational messages with a blue background
        $outputStyle = new OutputFormatterStyle('white', 'blue');
        $output->getFormatter()->setStyle('info-bg', $outputStyle);

        // Retrieve the 'port' argument from the input
        $PORT = $input->getArgument('port');
        // Display the server start message with the port number
        $io->writeln("\n\r<info-bg> INFO </info-bg> Server started on <fg=blue>http://localhost:$PORT</>\n");
        // Execute the shell command to start the PHP built-in server on the specified port
        shell_exec("php -S localhost:$PORT -t public");
        return Command::SUCCESS;
    },
    [
        // Define the 'port' argument for the command
        'port' => [
            InputArgument::OPTIONAL, // Argument is optional
            'Specifies the port for the server (default is 8000)', // Description of the argument
            '8000' // Default value for the port if not provided
        ]
    ]
);

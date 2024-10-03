<?php

use Lithe\Console\Line;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;

// Create the command
return Line::create(
    'make:middleware', // Command name
    'Create a middleware', // Command description
    function (InputInterface $input, OutputInterface $output) {
        $io = new SymfonyStyle($input, $output);

        // Define a custom style for informational messages with a blue background
        $outputStyle = new OutputFormatterStyle('white', 'blue');
        $output->getFormatter()->setStyle('info-bg', $outputStyle);

        // Get the middleware name argument from the input
        $name = $input->getArgument('name');
        // Define the path for the new middleware file
        $middlewarePath = PROJECT_ROOT . "/Http/Middleware/{$name}.php";
        // Define the directory for the new middleware file
        $middlewareDir = dirname($middlewarePath);

        // Verify and create the middleware directory if it does not exist
        verifyAndCreateDirectory($middlewareDir, $io);
        
        // Verify if the middleware file already exists
        verifyMiddlewareExists($middlewarePath, $io);

        // Generate the content for the new middleware file
        $middlewareContent = generateMiddlewareContent($name);
        // Write the content to the file
        file_put_contents($middlewarePath, $middlewareContent);

        $project_root = PROJECT_ROOT;

        // Output success message
        $io->writeln("\n\r<info-bg> INFO </info-bg> Middleware [{$project_root}/Http/Middleware/{$name}.php] created successfully.\n");

        return Command::SUCCESS;
    },
    [
        // Define the 'name' argument for the command
        'name' => [
            InputArgument::REQUIRED, // The argument is required
            'Middleware name' // Description of the argument
        ]
    ]
);

// Function to verify if the middleware file already exists
function verifyMiddlewareExists($middlewarePath, SymfonyStyle $io)
{
    if (file_exists($middlewarePath)) {
        // Output a warning if the file already exists
        $io->warning("The Middleware already exists in {$middlewarePath}.");
        // Exit with a failure status
        exit(Command::FAILURE);
    }
}

// Function to generate the content for the new middleware file
function generateMiddlewareContent($name)
{
    return <<<PHP
<?php

use Lithe\Http\Request;
use Lithe\Http\Response;

define('{$name}', function (Request \$req, Response \$res, callable \$next)
{
    // Your middleware logic goes here
});
PHP;
}

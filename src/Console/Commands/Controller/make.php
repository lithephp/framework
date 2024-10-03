<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Lithe\Console\Line;

return Line::create(
    'make:controller',
    'Create a Controller',
    function (InputInterface $input, OutputInterface $output) {
        $io = new SymfonyStyle($input, $output);

        // Define a custom style for informational messages with a blue background
        $outputStyle = new OutputFormatterStyle('white', 'blue');
        $output->getFormatter()->setStyle('info-bg', $outputStyle);

        // Get the controller name argument from the input
        $name = $input->getArgument('name');
        // Define the path for the new controller file
        $controllerPath = PROJECT_ROOT . "/Http/Controllers/{$name}.php";
        // Define the directory for the new controller file
        $controllerDir = dirname($controllerPath);

        // Verify and create the controller directory if it does not exist
        verifyAndCreateDirectory($controllerDir, $io);

        // Verify if the controller file already exists
        if (controllerExists($controllerPath)) {
            $io->warning("The controller already exists in {$controllerPath}.");
            return Command::FAILURE;
        }

        // Generate the content for the new controller file
        $controllerContent = generateControllerContent($name);
        // Write the content to the file
        file_put_contents($controllerPath, $controllerContent);

        // Output success message
        $io->writeln("\n\r<info-bg> INFO </info-bg> Controller [{$controllerPath}] created successfully.\n");

        return Command::SUCCESS;
    },
    [
        // Define the 'name' argument for the command
        'name' => [
            InputArgument::REQUIRED, // The argument is required
            'Controller name' // Description of the argument
        ]
    ]
);

// Function to verify if the controller file already exists
function controllerExists($controllerPath)
{
    return file_exists($controllerPath);
}

// Function to generate the content for the new controller file
function generateControllerContent($name)
{
    return <<<PHP
<?php

namespace App\Http\Controllers;

use Lithe\Http\Request;
use Lithe\Http\Response;

class {$name}
{
    // Your controller logic goes here
}
PHP;
}

<?php

use Lithe\Console\Line;
use Lithe\Support\Env;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

// Create the command
return Line::create(
    'make:model', // Command name
    'Create a Model', // Command description
    function (InputInterface $input, OutputInterface $output) {
        $io = new SymfonyStyle($input, $output);

        // Retrieve the 'name' argument for the model
        $name = $input->getArgument('name');
        // Define the path where the new model file will be created
        $modelPath = PROJECT_ROOT . "/models/{$name}.php";
        // Define the directory for the new model file
        $modelDir = dirname($modelPath);
        // Get the specified template option, if any
        $template = $input->getOption('template');

        // Create a custom style for informational messages with a blue background
        $outputStyle = new OutputFormatterStyle('white', 'blue');
        $output->getFormatter()->setStyle('info-bg', $outputStyle);

        // Define available templates for the model
        $templates = [
            'default' => [
                'use' => 'use Lithe\Database\Manager as DB;',
                'extends' => ''
            ],
            'eloquent' => [
                'use' => 'use Illuminate\Database\Eloquent\Model;',
                'extends' => 'extends Model'
            ],
        ];

        // Retrieve the database connection method from environment variables
        $db_connection_method = Env::get('DB_CONNECTION_METHOD', 'default');

        // Use the specified database connection method as the template
        if ($template === null) {
            // If no template option is provided, use the environment method
            $template = isset($templates[$db_connection_method]) ? $db_connection_method: 'default';
        }

        // Validate the specified template
        if (!isset($templates[$template])) {
            $io->error('Invalid template');
            return Command::FAILURE;
        }

        // Verify and create the directory if it does not exist
        verifyAndCreateDirectory($modelDir, $io);

        // Check if the model file already exists
        if (file_exists($modelPath)) {
            $io->warning("The model already exists in {$modelPath}.");
            return Command::FAILURE;
        }

        // Generate the content for the new model file
        $modelContent = <<<PHP
<?php
namespace App\Models;

{$templates[$template]['use']}

class {$name} {$templates[$template]['extends']}
{
    // Your model logic goes here
}
PHP;

        // Write the generated content to the model file
        file_put_contents($modelPath, $modelContent);
        $project_root = PROJECT_ROOT;
        // Output a success message indicating the model was created
        $io->writeln("\n\r<info-bg> INFO </info-bg> Model [{$project_root}/models/{$name}.php] created successfully.\n");

        return Command::SUCCESS;
    },
    [
        // Define the 'name' argument for the command
        'name' => [
            InputArgument::REQUIRED, // Argument is required
            'Model name' // Description of the argument
        ]
    ],
    [
        // Define the 'template' option for the command
        'template' => [
            null, // No short option
            InputOption::VALUE_REQUIRED, // Option requires a value
            'Template type', // Description of the option
            null // Default value if not provided
        ]
    ]
);

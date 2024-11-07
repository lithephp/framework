<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Lithe\Console\Line;
use Lithe\Support\Env;

return Line::create(
    'make:seeder', // Command name
    'Create a new seeder file', // Command description
    function (InputInterface $input, OutputInterface $output) {
        // Define a custom output style for informational messages with a blue background
        $outputStyle = new OutputFormatterStyle('white', 'blue');
        $output->getFormatter()->setStyle('info-bg', $outputStyle);
        $io = new SymfonyStyle($input, $output);

        // Define different seeder templates based on database connection method
        $templates = [
            'default' => <<<PHP
<?php

class {className}
{
    /**
     * Run the database seeds.
     *
     * @param void
     */
    public function run(): void
    {
        // Logic to seed the database
    }
}
PHP,
            'mysqli' => <<<PHP
<?php

class {className}
{
    /**
     * Run the database seeds.
     *
     * @param mysqli \$db Database connection instance
     */
    public function run(mysqli \$db): void
    {
        // Logic to seed the database using mysqli
    }
}
PHP,
            'pdo' => <<<PHP
<?php

class {className}
{
    /**
     * Run the database seeds.
     *
     * @param PDO \$db Database connection instance
     */
    public function run(PDO \$db): void
    {
        // Logic to seed the database using PDO
    }
}
PHP,
        ];

        // Retrieve the 'name' argument from the input
        $name = $input->getArgument('name');

        // Validate the seeder name
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            $io->error('The seeder name must contain only alphanumeric characters and underscores.');
            return Command::FAILURE;
        }

        // Get the specified template option, if any
        $template = $input->getOption('template');

        if ($template) {
            // Check if the template provided via input is valid
            if (!isset($templates[$template])) {
                $io->error('Invalid template');
                return Command::FAILURE;
            }
        } else {
            // Try to get the template from the environment variable
            $DB_CONNECTION_METHOD = Env::get('DB_CONNECTION_METHOD', 'default');

            // Check if the template from the environment variable is valid
            if (!isset($templates[$DB_CONNECTION_METHOD])) {
                // If the template is not valid, set 'default' as fallback
                $template = 'default';
            } else {
                // Otherwise, use the template from the environment variable
                $template = $DB_CONNECTION_METHOD;
            }
        }

        // Define the path where seeder files will be stored
        $path = PROJECT_ROOT . '/database/seeders/';
        // Create the directory if it does not exist
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        // Generate the seeder file content based on the selected template
        $content = str_replace('{className}', $name, $templates[$template]);
        // Create the file path using the seeder name
        $filePath = $path . $name . '.php';

        // Write the content to the seeder file
        file_put_contents($filePath, $content);

        // Output a success message indicating the seeder file was created
        $io->writeln("\n\r<info-bg> INFO </info-bg> Seeder [$filePath] created successfully. You can now run it using your seeding logic.\n");

        return Command::SUCCESS;
    },
    [
        // Define the 'name' argument as required for the command
        'name' => [
            InputArgument::REQUIRED,
            'Seeder name'
        ],
    ],
    [
        // Define the 'template' option for the command
        'template' => [null, InputOption::VALUE_REQUIRED, 'Template type', '']
    ]
);

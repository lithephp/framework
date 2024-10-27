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
    'make:migration', // Command name
    'Create a migration file', // Command description
    function (InputInterface $input, OutputInterface $output) {
        // Define a custom output style for informational messages with a blue background
        $outputStyle = new OutputFormatterStyle('white', 'blue');
        $output->getFormatter()->setStyle('info-bg', $outputStyle);
        $io = new SymfonyStyle($input, $output);

        // Define different migration templates based on database connection method
        $templates = [
            'default' => <<<PHP
return new class
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Code to apply the migration goes here
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Code to revert the migration goes here
    }
};
PHP,
            'mysqli' => <<<PHP
return new class
{
    /**
     * Run the migrations.
     *
     * @param mysqli \$db
     * @return void
     */
    public function up(mysqli \$db): void
    {
        // Code to apply the migration using mysqli goes here
    }

    /**
     * Reverse the migrations.
     *
     * @param mysqli \$db
     * @return void
     */
    public function down(mysqli \$db): void
    {
        // Code to revert the migration using mysqli goes here
    }
};
PHP,
            'eloquent' => <<<PHP
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Capsule::schema(); // Code to apply the migration using Eloquent
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Capsule::schema(); // Code to revert the migration using Eloquent
    }
};
PHP,
            'pdo' => <<<PHP
return new class
{
    /**
     * Run the migrations.
     *
     * @param PDO \$db
     * @return void
     */
    public function up(PDO \$db): void
    {
        // Code to apply the migration using PDO goes here
    }

    /**
     * Reverse the migrations.
     *
     * @param PDO \$db
     * @return void
     */
    public function down(PDO \$db): void
    {
        // Code to revert the migration using PDO goes here
    }
};
PHP,
        ];

        // Retrieve the 'name' argument from the input
        $name = $input->getArgument('name');
        // Retrieve the 'template' option from the input or default to the environment variable
        $template = $input->getOption('template') ?: (Env::get('DB_CONNECTION_METHOD', 'default'));

        // Check if the specified template is valid
        if (!isset($templates[$template])) {
            $io->error('Invalid template');
            return Command::FAILURE;
        }

        // Ensure the migration name is provided
        if (empty($name)) {
            $io->warning('The name of the migration is mandatory.');
            return Command::FAILURE;
        }

        // Define the path where migration files will be stored
        $path = PROJECT_ROOT . '/database/migrations/';
        // Create the directory if it does not exist
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        // Generate the migration file content based on the selected template
        $content = $templates[$template];
        // Create a unique file name based on the current timestamp and migration name
        $filePath = $path . date('Y') . "_" . date('m') . "_" . date('d') . "_" . date('His') . '_' . $name . '.php';

        // Write the content to the migration file
        file_put_contents($filePath, "<?php\n\n" . $content);

        // Output a success message indicating the migration file was created
        $io->writeln("\n\r<info-bg> INFO </info-bg> Migration [$filePath] created successfully.\n");

        return Command::SUCCESS;
    },
    [
        // Define the 'name' argument as required for the command
        'name' => [
            InputArgument::REQUIRED,
            'Migration name'
        ],
    ], 
    [
        // Define the 'template' option for the command
        'template' => [null, InputOption::VALUE_REQUIRED, 'Template type', '']
    ]
);

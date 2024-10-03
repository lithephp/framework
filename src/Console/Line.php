<?php

namespace Lithe\Console;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Application as SymfonyApplication;
use InvalidArgumentException;
use Lithe\Support\import;

class Line
{
    /**
     * @var SymfonyApplication
     */
    private static $application;

    /**
     * Initializes the static SymfonyApplication instance.
     */
    private static function initialize(): void
    {
        if (self::$application === null) {
            self::$application = new SymfonyApplication();
        }
    }

    /**
     * Loads and registers default commands.
     */
    private static function loadDefaultCommands(): void
    {
        import::dir(__DIR__ . '/Parts')->get();

        self::use([
            include __DIR__ . '/Commands/Server/serve.php',
            include __DIR__ . '/Commands/Models/make.php',
            include __DIR__ . '/Commands/Middleware/make.php',
            include __DIR__ . '/Commands/Controller/make.php',
            include __DIR__ . '/Commands/Keys/generate.php',
            include __DIR__ . '/Commands/Migrations/make.php',
            include __DIR__ . '/Commands/Migrations/migrate.php',
            include __DIR__ . '/Commands/Migrations/refresh.php',
            include __DIR__ . '/Commands/Migrations/reset.php',
            include __DIR__ . '/Commands/Migrations/rollback.php',
        ]);
    }

    /**
     * Registers multiple commands.
     *
     * @param SymfonyCommand[] $commands Array of SymfonyCommand objects.
     * @throws InvalidArgumentException If any item is not an instance of SymfonyCommand.
     */
    public static function use(array $commands): void
    {
        self::initialize();

        foreach ($commands as $command) {
            if ($command instanceof SymfonyCommand) {
                self::$application->add($command);
            } else {
                throw new InvalidArgumentException('All items in the array must be instances of SymfonyCommand.');
            }
        }
    }

    /**
     * Runs the console application.
     *
     * @param array $args Command line arguments
     * @return int
     */
    public static function listen(array $args = []): int
    {
        self::initialize();
        self::loadDefaultCommands();

        try {
            return self::$application->run(new \Symfony\Component\Console\Input\ArgvInput($args));
        } catch (\Exception $e) {
            // You might want to log the exception or handle it in another way
            echo 'Error: ' . $e->getMessage();
            return 1;
        }
    }

    /**
     * Creates a new command instance.
     *
     * @param string $name Command name
     * @param string $description Command description
     * @param callable $handler Command handler
     * @param array $arguments Command arguments
     * @param array $options Command options
     * @return SymfonyCommand
     */
    public static function create(
        string $name,
        string $description,
        callable $handler,
        array $arguments = [],
        array $options = []
    ): SymfonyCommand {
        return new class($name, $description, $handler, $arguments, $options) extends SymfonyCommand
        {
            private $handler;

            public function __construct(
                string $name,
                string $description,
                callable $handler,
                array $arguments = [],
                array $options = []
            ) {
                parent::__construct($name);
                $this->setDescription($description);
                $this->handler = $handler;

                foreach ($arguments as $argName => $argOptions) {
                    $this->addArgument($argName, ...$argOptions);
                }

                foreach ($options as $optName => $optOptions) {
                    $this->addOption($optName, ...$optOptions);
                }
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                return (int) call_user_func($this->handler, $input, $output);
            }
        };
    }
}

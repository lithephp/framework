<?php

namespace Lithe\Database;

use PDO;
use PDOException;
use RuntimeException;
use Exception;
use Lithe\Support\Env;
use Lithe\Support\Log;

class Manager
{
    private static $settings = [];

    // Constants for environment variable keys
    private const DB_CONNECTION_METHOD = 'DB_CONNECTION_METHOD';
    private const DB_CONNECTION = 'DB_CONNECTION';
    private const DB_HOST = 'DB_HOST';
    private const DB_NAME = 'DB_NAME';
    private const DB_USERNAME = 'DB_USERNAME';
    private const DB_PASSWORD = 'DB_PASSWORD';
    private const DB_SHOULD_INITIATE = 'DB_SHOULD_INITIATE';

    /**
     * Creates the database if it does not exist.
     *
     * @param object $dbConfig The database configuration.
     * @throws RuntimeException If there is an error connecting to the database.
     */
    private static function createDatabaseIfNotExists(object $dbConfig)
    {
        try {
            $pdo = new PDO(self::getDsn($dbConfig->host), $dbConfig->username, $dbConfig->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SHOW DATABASES LIKE ?");
            $stmt->execute([$dbConfig->database]);

            if (!$stmt->fetch()) {
                $pdo->exec("CREATE DATABASE `{$dbConfig->database}` CHARACTER SET utf8 COLLATE utf8_unicode_ci");
            }
        } catch (PDOException $e) {
            // Log detailed error
            Log::error("Database connection error: " . $e->getMessage());

            die('An error occurred while connecting to the database.');
        }
    }

    /**
     * Returns the DSN for PDO connection.
     *
     * @param string $dbHost The database host.
     * @return string
     */
    private static function getDsn(string $dbHost)
    {
        $database = Env::get(self::DB_CONNECTION, 'mysql');
        return "$database:host=$dbHost";
    }

    /**
     * Returns the default settings for database connections.
     *
     * @return array
     */
    private static function defaultSettings()
    {
        return include __DIR__ . '/../Config/database.php';
    }

    /**
     * Configures a database connection.
     *
     * @param string $name The name of the connection.
     * @param callable $config The configuration function for the connection.
     */
    public static function configure(string $name, callable $config)
    {
        self::$settings[$name] = $config;
    }

    /**
     * Initializes and returns the configured database connection.
     *
     * @param string|null $name The name of the database configuration to initialize.
     * @return mixed The result of the database connection initialization.
     * @throws RuntimeException If there is an error setting up the connection.
     * @throws Exception If the specified database configuration is not found.
     */
    public static function initialize(string $name = null)
    {
        try {
            $requiredEnvVariables = [
                self::DB_CONNECTION_METHOD,
                self::DB_CONNECTION,
                self::DB_HOST,
                self::DB_NAME,
                self::DB_USERNAME,
                self::DB_PASSWORD,
                self::DB_SHOULD_INITIATE
            ];

            foreach ($requiredEnvVariables as $envVariable) {
                if (!Env::has($envVariable)) {
                    throw new RuntimeException("Missing environment variable: $envVariable");
                }
            }

            if (!filter_var(Env::get(self::DB_SHOULD_INITIATE), FILTER_VALIDATE_BOOLEAN)) {
                return null; // Return null if initialization is not required
            }

            if (!$name) {
                $name = Env::get(self::DB_CONNECTION_METHOD);
            }

            $settings = array_merge(self::defaultSettings(), self::$settings);

            if (isset($settings[$name])) {
                $dbConfig = (object) [
                    'driver' => Env::get(self::DB_CONNECTION, 'mysql'),
                    'host' => Env::get(self::DB_HOST, '127.0.0.1'),
                    'database' => Env::get(self::DB_NAME, 'test'),
                    'username' => Env::get(self::DB_USERNAME, 'root'),
                    'password' => Env::get(self::DB_PASSWORD, '')
                ];

                // Check if we are in production mode
                if (!filter_var(Env::get('APP_PRODUCTION_MODE', false), FILTER_VALIDATE_BOOLEAN)) {
                    self::createDatabaseIfNotExists($dbConfig);
                }

                return self::setupConnection($name, $dbConfig, $settings);
            } else {
                throw new Exception("Database configuration '$name' not found.");
            }
        } catch (Exception $e) {
            Log::error("Error initializing the database: " . $e->getMessage());
            die("An error occurred while initializing the database: " . $e->getMessage());
        }
    }

    private static function setupConnection(string $name, object $dbConfig, array $settings)
    {
        try {
            return $settings[$name]($dbConfig);
        } catch (Exception $e) {
            Log::error("Error setting up the '$name' connection: " . $e->getMessage());
            throw new RuntimeException('An error occurred while setting up the database connection: ' . $e->getMessage());
        }
    }
}

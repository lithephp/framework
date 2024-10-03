<?php

use Lithe\Support\Log;

return [
    'eloquent' => function (object $dbConfig) {
        if (class_exists('Illuminate\Database\Capsule\Manager')) {
            $capsule = new \Illuminate\Database\Capsule\Manager;
            $capsule->addConnection([
                'driver' => $dbConfig->driver ?? 'mysql',
                'host' => $dbConfig->host ?? '127.0.0.1',
                'database' => $dbConfig->database ?? 'test',
                'username' => $dbConfig->username ?? 'root',
                'password' => $dbConfig->password ?? '',
                'charset' => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix' => '',
            ]);
            $capsule->setAsGlobal();
            $capsule->bootEloquent();
            return $capsule;
        } else {
            throw new RuntimeException('Eloquent is not installed.');
        }
    },
    'pdo' => function (object $dbConfig) {
        try {
            return new PDO(
                self::getDsn($dbConfig->host) . ';dbname=' . $dbConfig->database,
                $dbConfig->username,
                $dbConfig->password
            );
        } catch (PDOException $e) {
            // Log detailed error
            Log::error("Database connection error: " . $e->getMessage());
            
            die('An error occurred while connecting to the database.');
        }
    },
    'mysqli' => function (object $dbConfig) {
        $mysqli = new \mysqli(
            $dbConfig->host,
            $dbConfig->username,
            $dbConfig->password,
            $dbConfig->database
        );

        if ($mysqli->connect_error) {
            // Log detailed error
            Log::error("Database connection error: " . $mysqli->connect_error);
            
            die('An error occurred while connecting to the database.');
        }

        return $mysqli;
    }
];
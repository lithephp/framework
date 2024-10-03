<?php

class Migration
{
    protected static $pdo = null;
    protected static $table = 'migrations';

    /**
     * Initialize the PDO connection.
     *
     * @param PDO|null $pdo The PDO instance to use
     */
    public static function init(PDO|null $pdo)
    {
        if (!$pdo) {
            throw new RuntimeException('Database connection is not initialized.');
        }
        self::$pdo = $pdo;
    }

    /**
     * Get the PDO connection or throw an exception if it's not initialized.
     *
     * @return PDO The PDO instance
     * @throws RuntimeException If the PDO connection is not initialized
     */
    private static function getPdo(): PDO
    {
        if (!self::$pdo) {
            throw new RuntimeException('Database connection is not initialized.');
        }
        return self::$pdo;
    }

    /**
     * Create the migrations table if it doesn't exist.
     *
     * @throws RuntimeException If there is an error executing the query
     */
    public static function createTableIfItDoesntExist()
    {
        try {
            $pdo = self::getPdo();
            $query = "CREATE TABLE IF NOT EXISTS " . self::$table . " (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration TEXT NOT NULL,
                batch INT NOT NULL
            )";
            $pdo->exec($query);
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to create migrations table: " . $e->getMessage());
        }
    }

    /**
     * Find the last batch of migrations.
     *
     * @return int|bool The last batch number, or false if not found
     */
    public static function findLastBatch()
    {
        try {
            $pdo = self::getPdo();
            $stmt = $pdo->query("SELECT batch FROM " . self::$table . " ORDER BY id DESC LIMIT 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['batch'] : false;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Add a new migration.
     *
     * @param string $migration The migration content
     * @param int $batch The batch number
     * @return bool True on success, false on failure
     */
    public static function add($migration, $batch)
    {
        try {
            $pdo = self::getPdo();
            $stmt = $pdo->prepare("INSERT INTO " . self::$table . " (migration, batch) VALUES (:migration, :batch)");
            $stmt->bindParam(':migration', $migration);
            $stmt->bindParam(':batch', $batch, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Delete a migration by ID.
     *
     * @param int $id The migration ID
     * @return bool True on success, false on failure
     */
    public static function delete($id)
    {
        try {
            $pdo = self::getPdo();
            $stmt = $pdo->prepare("DELETE FROM " . self::$table . " WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Save a migration (update if exists, insert if not).
     *
     * @param int $id The migration ID
     * @param string $migration The migration content
     * @param int $batch The batch number
     * @return bool True on success, false on failure
     */
    public static function save($id, $migration, $batch)
    {
        try {
            $pdo = self::getPdo();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . self::$table . " WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $exists = $stmt->fetchColumn() > 0;

            if ($exists) {
                $stmt = $pdo->prepare("UPDATE " . self::$table . " SET migration = :migration, batch = :batch WHERE id = :id");
            } else {
                $stmt = $pdo->prepare("INSERT INTO " . self::$table . " (id, migration, batch) VALUES (:id, :migration, :batch)");
            }

            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':migration', $migration);
            $stmt->bindParam(':batch', $batch, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve migrations with a WHERE clause.
     *
     * @param string $column The column name
     * @param mixed $value The value to match
     * @return array|bool Array of migrations, or false on failure
     */
    public static function getWhere($column, $value)
    {
        try {
            $pdo = self::getPdo();
            $stmt = $pdo->prepare("SELECT * FROM " . self::$table . " WHERE {$column} = :value ORDER BY id DESC");
            $stmt->bindParam(':value', $value);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve migrations ordered by a specific column.
     *
     * @param string $column The column name
     * @param string $direction The sorting direction (default: 'ASC')
     * @return array|bool Array of migrations, or false on failure
     */
    public static function getOrdered($column, $direction = 'ASC')
    {
        try {
            $pdo = self::getPdo();
            $stmt = $pdo->prepare("SELECT * FROM " . self::$table . " ORDER BY {$column} {$direction}");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve all migrations.
     *
     * @return array|bool Array of migrations, or false on failure
     */
    public static function all()
    {
        try {
            $pdo = self::getPdo();
            $stmt = $pdo->query("SELECT * FROM " . self::$table);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}

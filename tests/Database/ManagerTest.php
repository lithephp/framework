<?php

namespace Tests\Database;

use Lithe\Database\Manager;
use Lithe\Support\Env;
use Lithe\Support\Log;
use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Env::load(__DIR__);

        Log::dir(__DIR__ . '/logs');
    }

    protected function tearDown(): void
    {
        Env::set('DB_CONNECTION_METHOD', 'pdo');
        Env::set('DB_CONNECTION', 'mysql');
        Env::set('DB_HOST', 'localhost');
        Env::set('DB_NAME', 'lithe');
        Env::set('DB_USERNAME', 'root');
        Env::set('DB_PASSWORD', '');
        Env::set('DB_SHOULD_INITIATE', true);
    }

    public function testInitializeSuccess()
    {
        $result = Manager::initialize();
        $this->assertNotNull($result, 'Expected a connection instance, but got null.');
    }

    public function testInitializeWithInvalidEnv()
    {
        Env::set('DB_SHOULD_INITIATE', false);
        $result = Manager::initialize(null, true);
        $this->assertNotNull($result, 'Expected a connection instance, but got null when initiating.');
    }

    
    public function testConnectionReturnsCurrentConnection()
    {
        Manager::initialize(); // Inicializa para garantir que temos uma conexão
        $connection = Manager::connection();
        $this->assertNotNull($connection, 'Expected to get a connection, but got null.');
    }
}

<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\MySQLConnector;
use App\Kernel\Exceptions\KernelException;
use App\Kernel\Interfaces\Databases\ConnectorInterface;
use App\Kernel\Connector\DatabaseException;

class MysqlConnectorTest extends TestCase
{
 /* 
    this class tests only creation, no sql function.
    Valid credentials are needed to run these tests
*/

    private array $validEnv;

    protected function setUp(): void
    {
        parent::setUp();
        MySQLConnector::resetInstance();
        $this->validEnv = [
            'host' => 'localhost', //Change to your settings
            'db' => 'your_db', //Change to your settings
            'user' => 'your_login',  //Change to your settings
            'pass' => 'your_pass' //Change to your settings
        ];
    }

    protected function tearDown(): void
    {
        MySQLConnector::resetInstance();
        parent::tearDown();
    }

    public function testGetInstanceThrowsExceptionWhenNotInitialized(): void
    {
        $this->expectException(KernelException::class);
        $this->expectExceptionMessage('Database not initilized');
        MySQLConnector::getInstance();
    }

    public function testGetInstanceThrowsExceptionWhenBadEnv(): void
    {
        $env = [
            'hoest' => 'localhost',
            'db' => 'test_database',
            'user' => 'test_user',
            'pass' => 'test_password'
        ];
        $this->expectException(KernelException::class);
        $this->expectExceptionMessage('Env datas does not exist');
        MySQLConnector::getInstance($env);
    }

    public function testGetInstanceThrowsExceptionWhenFalseEnv(): void
    {
        $env = [
            'host' => 'localhost',
            'db' => 'test_database',
            'user' => 'test_user',
            'pass' => 'test_password'
        ];
        $this->expectException(DatabaseException::class);
        $this->expectExceptionCode(1045);
        MySQLConnector::getInstance($env);
    }

    public function testGetInstanceThrowsExceptionWhenBadHost(): void
    {
        $env = [
            'host' => '192.168.1.111',
            'db' => 'test_database',
            'user' => 'test_user',
            'pass' => 'test_password'
        ];
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches("/Failed to connect to database after/");
        $this->expectExceptionCode(2002);
        MySQLConnector::getInstance($env);
    }


    public function testGetInstanceCreatesInstanceWithValidEnv(): void
    {
        // Mock PDO to avoid actual database connection      
        $instance = MySQLConnector::getInstance($this->validEnv);
        $this->assertInstanceOf(ConnectorInterface::class, $instance);
    }

    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = MySQLConnector::getInstance($this->validEnv);
        $instance2 = MySQLConnector::getInstance();
        $this->assertSame($instance1, $instance2);
    }
}

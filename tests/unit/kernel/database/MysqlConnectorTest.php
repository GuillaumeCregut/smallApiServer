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
            'DB_HOST' => 'localhost', //Change to your settings
            'DB_NAME' => 'your_db', //Change to your settings
            'DB_USER' => 'your_login',  //Change to your settings
            'DB_PASS' => 'your_pass' //Change to your settings
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
            'DB_HOST' => 'localhost',
            'DB_NAME' => 'test_database',
            'DB_USER' => 'test_user',
            'DB_PASS' => 'test_password'
        ];
        $this->expectException(DatabaseException::class);
        $this->expectExceptionCode(1045);
        MySQLConnector::getInstance($env);
    }

    public function testGetInstanceThrowsExceptionWhenBadHost(): void
    {
        $env = [
            'DB_HOST' => '192.168.1.111',
            'DB_NAME' => 'test_database',
            'DB_USER' => 'test_user',
            'DB_PASS' => 'test_password'
        ];
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches("/Failed to connect to database after/");
        $this->expectExceptionCode(2002);
        MySQLConnector::getInstance($env);
    }


    public function testGetInstanceCreatesInstanceWithValidEnv(): void
    {    
        $this->markTestSkipped('Needs DB connection');
        $instance = MySQLConnector::getInstance($this->validEnv);
        $this->assertInstanceOf(ConnectorInterface::class, $instance);
    }

    public function testGetInstanceReturnsSameInstance(): void
    {
        $this->markTestSkipped('Needs DB connection');
        $instance1 = MySQLConnector::getInstance($this->validEnv);
        $instance2 = MySQLConnector::getInstance();
        $this->assertSame($instance1, $instance2);
    }
}

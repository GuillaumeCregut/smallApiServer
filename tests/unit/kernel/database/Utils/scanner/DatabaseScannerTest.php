<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Exceptions\KernelException;
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Connector\Utils\Scanner\DatabaseScanner;
use App\Kernel\Connector\Utils\Scanner\MySQLScannerDriver;

class DatabaseScannerTest extends TestCase
{
    private function makeConnectorWithDriver(string $driverName): ConnectorInterface
    {
        $pdo = $this->createStub(PDO::class);
        $pdo->method('getAttribute')
            ->willReturn($driverName);

        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('getConnection')
            ->willReturn($pdo);
        $connector->method('fetchQueryOnce')
            ->willReturn(['db' => 'test_db']);
        $connector->method('fetchQuery')
            ->willReturn([]);

        return $connector;
    }

    public function testInstantiatesMySQLDriver(): void
    {
        $connector = $this->makeConnectorWithDriver('mysql');
        $scanner   = new DatabaseScanner($connector);

        $this->assertInstanceOf(
            MySQLScannerDriver::class,
            $scanner->getDriver()
        );
    }

    public function testThrowsOnUnsupportedDriver(): void
    {
        $connector = $this->makeConnectorWithDriver('firebird');

        $this->expectException(KernelException::class);
        new DatabaseScanner($connector);
    }

    public function testScanDelegatesToDriver(): void
    {
        $connector = $this->makeConnectorWithDriver('mysql');

        // fetchQuery returns empty tables list → scan returns []
        $scanner = new DatabaseScanner($connector);
        $result  = $scanner->scan();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testScanReturnsDriverOutput(): void
    {
        $pdo = $this->createStub(PDO::class);
        $pdo->method('getAttribute')->willReturn('mysql');

        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('getConnection')->willReturn($pdo);
        $connector->method('fetchQueryOnce')->willReturn(['db' => 'test_db']);
        $connector->method('fetchQuery')
            ->willReturnCallback(function (string $sql) {
                if (stripos($sql, 'SHOW TABLES') !== false) {
                    return [['Tables_in_test_db' => 'users']];
                }
                if (stripos($sql, 'information_schema.columns') !== false) {
                    return [
                        ['column_name' => 'id', 'data_type' => 'int', 'column_type' => 'int(11)', 'is_nullable' => 'NO', 'column_default' => null],
                    ];
                }
                return [];
            });

        $scanner = new DatabaseScanner($connector);
        $schema  = $scanner->scan();

        $this->assertArrayHasKey('users', $schema);
        $this->assertArrayHasKey('columns', $schema['users']);
        $this->assertArrayHasKey('id', $schema['users']['columns']);
    }
}

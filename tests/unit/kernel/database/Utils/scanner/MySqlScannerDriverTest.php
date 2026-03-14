<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Connector\Utils\Scanner\MySQLScannerDriver;

class MySQLScannerDriverTest extends TestCase
{
    private function makeConnector(array $fetchQueryMap = [], ?array $fetchQueryOnceReturn = null): ConnectorInterface
    {
        $connector = $this->createStub(ConnectorInterface::class);
 
        if (!empty($fetchQueryMap)) {
            $connector->method('fetchQuery')
                ->willReturnCallback(function (string $sql) use ($fetchQueryMap) {
                    foreach ($fetchQueryMap as $keyword => $result) {
                        if (stripos($sql, $keyword) !== false) {
                            return $result;
                        }
                    }
                    return [];
                });
        }
 
        if ($fetchQueryOnceReturn !== null) {
            $connector->method('fetchQueryOnce')
                ->willReturn($fetchQueryOnceReturn);
        } else {
            $connector->method('fetchQueryOnce')
                ->willReturn(['db' => 'test_db']);
        }
 
        return $connector;
    }
 
    public function testGetTablesReturnsTableNames(): void
    {
        $connector = $this->makeConnector([
            'SHOW TABLES' => [
                ['Tables_in_test_db' => 'users'],
                ['Tables_in_test_db' => 'posts'],
            ]
        ]);
 
        $driver = new MySQLScannerDriver($connector);
        $tables = $driver->getTables();
 
        $this->assertEquals(['users', 'posts'], $tables);
    }
 
    public function testGetTablesReturnsEmptyArrayWhenNoTables(): void
    {
        $connector = $this->makeConnector(['SHOW TABLES' => []]);
        $driver    = new MySQLScannerDriver($connector);
 
        $this->assertEmpty($driver->getTables());
    }
 
    public function testGetColumnsNormalizesBasicTypes(): void
    {
        $connector = $this->makeConnector([
            'information_schema.columns' => [
                ['column_name' => 'id',    'data_type' => 'int',     'column_type' => 'int(11)',      'is_nullable' => 'NO',  'column_default' => null],
                ['column_name' => 'email', 'data_type' => 'varchar', 'column_type' => 'varchar(255)', 'is_nullable' => 'NO',  'column_default' => null],
                ['column_name' => 'age',   'data_type' => 'int',     'column_type' => 'int(11)',      'is_nullable' => 'YES', 'column_default' => null],
            ]
        ]);
 
        $driver  = new MySQLScannerDriver($connector);
        $columns = $driver->getColumns('users');
 
        $this->assertArrayHasKey('id', $columns);
        $this->assertEquals('int',    $columns['id']['type']);
        $this->assertFalse($columns['id']['nullable']);
 
        $this->assertArrayHasKey('email', $columns);
        $this->assertEquals('string', $columns['email']['type']);
        $this->assertFalse($columns['email']['nullable']);
 
        $this->assertArrayHasKey('age', $columns);
        $this->assertTrue($columns['age']['nullable']);
 
        foreach ($columns as $col) {
            $this->assertEmpty($col['relation']);
        }
    }
 
    public function testGetColumnsNormalizesTinyint1AsBool(): void
    {
        $connector = $this->makeConnector([
            'information_schema.columns' => [
                ['column_name' => 'is_admin', 'data_type' => 'tinyint', 'column_type' => 'tinyint(1)', 'is_nullable' => 'NO', 'column_default' => null],
            ]
        ]);
 
        $driver  = new MySQLScannerDriver($connector);
        $columns = $driver->getColumns('users');
 
        $this->assertEquals('bool', $columns['is_admin']['type']);
    }
 
    public function testGetColumnsNormalizesJsonType(): void
    {
        $connector = $this->makeConnector([
            'information_schema.columns' => [
                ['column_name' => 'tags', 'data_type' => 'json', 'column_type' => 'json', 'is_nullable' => 'YES', 'column_default' => null],
            ]
        ]);
 
        $driver  = new MySQLScannerDriver($connector);
        $columns = $driver->getColumns('users');
 
        $this->assertEquals('json', $columns['tags']['type']);
    }
 
    public function testGetColumnsReturnsEmptyArrayWhenNoColumns(): void
    {
        $connector = $this->makeConnector(['information_schema.columns' => []]);
        $driver    = new MySQLScannerDriver($connector);
 
        $this->assertEmpty($driver->getColumns('empty_table'));
    }
 
    public function testGetPrimaryKeysReturnsPKColumnNames(): void
    {
        $connector = $this->makeConnector([
            "constraint_name = 'PRIMARY'" => [
                ['column_name' => 'id'],
            ]
        ]);
 
        $driver = new MySQLScannerDriver($connector);
        $pks    = $driver->getPrimaryKeys('users');
 
        $this->assertEquals(['id'], $pks);
    }
 
    public function testGetPrimaryKeysReturnsEmptyArrayWhenNoPK(): void
    {
        $connector = $this->makeConnector(["constraint_name = 'PRIMARY'" => []]);
        $driver    = new MySQLScannerDriver($connector);
 
        $this->assertEmpty($driver->getPrimaryKeys('users'));
    }
 
    public function testGetForeignKeysNormalizesToRelation(): void
    {
        $connector = $this->makeConnector([
            'referenced_table_name IS NOT NULL' => [
                [
                    'column_name'            => 'author_id',
                    'referenced_table_name'  => 'authors',
                    'referenced_column_name' => 'id',
                ]
            ]
        ]);
 
        $driver = new MySQLScannerDriver($connector);
        $fks    = $driver->getForeignKeys('posts');
 
        $this->assertArrayHasKey('author_id', $fks);
        $this->assertEquals('relation', $fks['author_id']['type']);
        $this->assertEquals('authors',  $fks['author_id']['relation']['entity']);
        $this->assertEquals('author_id', $fks['author_id']['relation']['key']);
    }
 
    public function testGetForeignKeysReturnsEmptyArrayWhenNoFK(): void
    {
        $connector = $this->makeConnector(['referenced_table_name IS NOT NULL' => []]);
        $driver    = new MySQLScannerDriver($connector);
 
        $this->assertEmpty($driver->getForeignKeys('users'));
    }
 
    public function testGetIndexesReturnsIndexes(): void
    {
        $connector = $this->makeConnector([
            'information_schema.statistics' => [
                ['index_name' => 'idx_email', 'column_name' => 'email', 'non_unique' => 0],
            ]
        ]);
 
        $driver  = new MySQLScannerDriver($connector);
        $indexes = $driver->getIndexes('users');
 
        $this->assertArrayHasKey('idx_email', $indexes);
        $this->assertTrue($indexes['idx_email']['unique']);
        $this->assertContains('email', $indexes['idx_email']['columns']);
    }
 
    public function testGetIndexesReturnsEmptyArrayWhenNoIndexes(): void
    {
        $connector = $this->makeConnector(['information_schema.statistics' => []]);
        $driver    = new MySQLScannerDriver($connector);
 
        $this->assertEmpty($driver->getIndexes('users'));
    }
 
    public function testScanReturnsFlatSchemaWithFKOverride(): void
    {
        $connector = $this->makeConnector([
            'SHOW TABLES' => [
                ['Tables_in_test_db' => 'posts'],
            ],
            'information_schema.columns' => [
                ['column_name' => 'id',       'data_type' => 'int', 'column_type' => 'int(11)', 'is_nullable' => 'NO',  'column_default' => null],
                ['column_name' => 'author_id', 'data_type' => 'int', 'column_type' => 'int(11)', 'is_nullable' => 'NO',  'column_default' => null],
            ],
            "constraint_name = 'PRIMARY'" => [
                ['column_name' => 'id'],
            ],
            'key_column_usage kcu' => [
                ['column_name' => 'author_id', 'referenced_table_name' => 'authors', 'referenced_column_name' => 'id'],
            ],
            'information_schema.statistics' => [],
        ]);
 
        $driver = new MySQLScannerDriver($connector);
        $schema = $driver->scan();
 
        $this->assertArrayHasKey('posts', $schema);
        $this->assertArrayHasKey('columns', $schema['posts']);
        $this->assertArrayHasKey('primary_keys', $schema['posts']);
        $this->assertArrayHasKey('indexes', $schema['posts']);
 
        // FK column must override basic int type
        $this->assertEquals('relation', $schema['posts']['columns']['author_id']['type']);
        $this->assertEquals('authors',  $schema['posts']['columns']['author_id']['relation']['entity']);
 
        // Regular column unchanged
        $this->assertEquals('int', $schema['posts']['columns']['id']['type']);
    }
 
    public function testScanReturnsEmptySchemaWhenNoTables(): void
    {
        $connector = $this->makeConnector(['SHOW TABLES' => []]);
        $driver    = new MySQLScannerDriver($connector);
 
        $this->assertEmpty($driver->scan());
    }
}

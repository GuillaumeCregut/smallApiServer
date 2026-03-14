<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Connector\Utils\Scanner\SQLiteScannerDriver;

class SqlisteScannerDriverTest extends TestCase
{
       private function makeConnector(array $fetchQueryMap = []): ConnectorInterface
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
 
        // SQLiteScannerDriver overrides __construct — no fetchQueryOnce needed
        return $connector;
    }
 
    // -------------------------------------------------------------------------
    // getTables
    // -------------------------------------------------------------------------
 
    public function testGetTablesReturnsTableNames(): void
    {
        $connector = $this->makeConnector([
            'sqlite_master' => [
                ['name' => 'users'],
                ['name' => 'posts'],
            ]
        ]);
 
        $driver = new SQLiteScannerDriver($connector);
        $tables = $driver->getTables();
 
        $this->assertEquals(['users', 'posts'], $tables);
    }
 
    public function testGetTablesReturnsEmptyArrayWhenNoTables(): void
    {
        $connector = $this->makeConnector(['sqlite_master' => []]);
        $driver    = new SQLiteScannerDriver($connector);
 
        $this->assertEmpty($driver->getTables());
    }
 
    // -------------------------------------------------------------------------
    // getColumns
    // -------------------------------------------------------------------------
 
    public function testGetColumnsNormalizesBasicTypes(): void
    {
        $connector = $this->makeConnector([
            'PRAGMA table_info' => [
                ['name' => 'id',    'type' => 'INTEGER', 'notnull' => 1, 'dflt_value' => null, 'pk' => 1],
                ['name' => 'email', 'type' => 'TEXT',    'notnull' => 1, 'dflt_value' => null, 'pk' => 0],
                ['name' => 'age',   'type' => 'INTEGER', 'notnull' => 0, 'dflt_value' => null, 'pk' => 0],
            ]
        ]);
 
        $driver  = new SQLiteScannerDriver($connector);
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
 
    public function testGetColumnsNormalizesRealAsFloat(): void
    {
        $connector = $this->makeConnector([
            'PRAGMA table_info' => [
                ['name' => 'price', 'type' => 'REAL', 'notnull' => 0, 'dflt_value' => null, 'pk' => 0],
            ]
        ]);
 
        $driver  = new SQLiteScannerDriver($connector);
        $columns = $driver->getColumns('products');
 
        $this->assertEquals('float', $columns['price']['type']);
    }
 
    public function testGetColumnsNormalizesTextAsJson(): void
    {
        $connector = $this->makeConnector([
            'PRAGMA table_info' => [
                ['name' => 'tags', 'type' => 'JSON', 'notnull' => 0, 'dflt_value' => null, 'pk' => 0],
            ]
        ]);
 
        $driver  = new SQLiteScannerDriver($connector);
        $columns = $driver->getColumns('users');
 
        $this->assertEquals('json', $columns['tags']['type']);
    }
 
    public function testGetColumnsNormalizesBoolAsInteger(): void
    {
        $connector = $this->makeConnector([
            'PRAGMA table_info' => [
                ['name' => 'is_admin', 'type' => 'BOOLEAN', 'notnull' => 1, 'dflt_value' => null, 'pk' => 0],
            ]
        ]);
 
        $driver  = new SQLiteScannerDriver($connector);
        $columns = $driver->getColumns('users');
 
        $this->assertEquals('bool', $columns['is_admin']['type']);
    }
 
    public function testGetColumnsReturnsEmptyArrayWhenNoColumns(): void
    {
        $connector = $this->makeConnector(['PRAGMA table_info' => []]);
        $driver    = new SQLiteScannerDriver($connector);
 
        $this->assertEmpty($driver->getColumns('empty_table'));
    }
 
    // -------------------------------------------------------------------------
    // getPrimaryKeys
    // -------------------------------------------------------------------------
 
    public function testGetPrimaryKeysReturnsPKColumnNames(): void
    {
        $connector = $this->makeConnector([
            'PRAGMA table_info' => [
                ['name' => 'id',    'type' => 'INTEGER', 'notnull' => 1, 'dflt_value' => null, 'pk' => 1],
                ['name' => 'email', 'type' => 'TEXT',    'notnull' => 1, 'dflt_value' => null, 'pk' => 0],
            ]
        ]);
 
        $driver = new SQLiteScannerDriver($connector);
        $pks    = $driver->getPrimaryKeys('users');
 
        $this->assertEquals(['id'], $pks);
    }
 
    public function testGetPrimaryKeysReturnsCompositePKInOrder(): void
    {
        $connector = $this->makeConnector([
            'PRAGMA table_info' => [
                ['name' => 'order_id',   'type' => 'INTEGER', 'notnull' => 1, 'dflt_value' => null, 'pk' => 2],
                ['name' => 'product_id', 'type' => 'INTEGER', 'notnull' => 1, 'dflt_value' => null, 'pk' => 1],
            ]
        ]);
 
        $driver = new SQLiteScannerDriver($connector);
        $pks    = $driver->getPrimaryKeys('order_items');
 
        // pk=1 comes before pk=2
        $this->assertEquals(['product_id', 'order_id'], $pks);
    }
 
    public function testGetPrimaryKeysReturnsEmptyArrayWhenNoPK(): void
    {
        $connector = $this->makeConnector([
            'PRAGMA table_info' => [
                ['name' => 'email', 'type' => 'TEXT', 'notnull' => 1, 'dflt_value' => null, 'pk' => 0],
            ]
        ]);
 
        $driver = new SQLiteScannerDriver($connector);
 
        $this->assertEmpty($driver->getPrimaryKeys('users'));
    }
 
    // -------------------------------------------------------------------------
    // getForeignKeys
    // -------------------------------------------------------------------------
 
    public function testGetForeignKeysNormalizesToRelation(): void
    {
        $connector = $this->makeConnector([
            'PRAGMA foreign_key_list' => [
                ['from' => 'author_id', 'table' => 'authors', 'to' => 'id'],
            ]
        ]);
 
        $driver = new SQLiteScannerDriver($connector);
        $fks    = $driver->getForeignKeys('posts');
 
        $this->assertArrayHasKey('author_id', $fks);
        $this->assertEquals('relation', $fks['author_id']['type']);
        $this->assertEquals('authors',  $fks['author_id']['relation']['entity']);
        $this->assertEquals('author_id',$fks['author_id']['relation']['key']);
    }
 
    public function testGetForeignKeysReturnsEmptyArrayWhenNoFK(): void
    {
        $connector = $this->makeConnector(['PRAGMA foreign_key_list' => []]);
        $driver    = new SQLiteScannerDriver($connector);
 
        $this->assertEmpty($driver->getForeignKeys('users'));
    }
 
    // -------------------------------------------------------------------------
    // getIndexes
    // -------------------------------------------------------------------------
 
    public function testGetIndexesReturnsIndexes(): void
    {
        $connector = $this->makeConnector([
            'PRAGMA index_list' => [
                ['name' => 'idx_email', 'unique' => 1, 'origin' => 'c'],
            ],
            'PRAGMA index_info' => [
                ['name' => 'email'],
            ],
        ]);
 
        $driver  = new SQLiteScannerDriver($connector);
        $indexes = $driver->getIndexes('users');
 
        $this->assertArrayHasKey('idx_email', $indexes);
        $this->assertTrue($indexes['idx_email']['unique']);
        $this->assertContains('email', $indexes['idx_email']['columns']);
    }
 
    public function testGetIndexesSkipsPrimaryKeyIndexes(): void
    {
        $connector = $this->makeConnector([
            'PRAGMA index_list' => [
                ['name' => 'sqlite_autoindex_users_1', 'unique' => 1, 'origin' => 'pk'],
                ['name' => 'idx_email',                'unique' => 0, 'origin' => 'c'],
            ],
            'PRAGMA index_info' => [
                ['name' => 'email'],
            ],
        ]);
 
        $driver  = new SQLiteScannerDriver($connector);
        $indexes = $driver->getIndexes('users');
 
        $this->assertArrayNotHasKey('sqlite_autoindex_users_1', $indexes);
        $this->assertArrayHasKey('idx_email', $indexes);
    }
 
    public function testGetIndexesReturnsEmptyArrayWhenNoIndexes(): void
    {
        $connector = $this->makeConnector(['PRAGMA index_list' => []]);
        $driver    = new SQLiteScannerDriver($connector);
 
        $this->assertEmpty($driver->getIndexes('users'));
    }
 
    // -------------------------------------------------------------------------
    // scan
    // -------------------------------------------------------------------------
 
    public function testScanReturnsFlatSchemaWithFKOverride(): void
    {
        $connector = $this->makeConnector([
            'sqlite_master' => [
                ['name' => 'posts'],
            ],
            'PRAGMA table_info' => [
                ['name' => 'id',        'type' => 'INTEGER', 'notnull' => 1, 'dflt_value' => null, 'pk' => 1],
                ['name' => 'author_id', 'type' => 'INTEGER', 'notnull' => 1, 'dflt_value' => null, 'pk' => 0],
            ],
            'PRAGMA foreign_key_list' => [
                ['from' => 'author_id', 'table' => 'authors', 'to' => 'id'],
            ],
            'PRAGMA index_list' => [],
        ]);
 
        $driver = new SQLiteScannerDriver($connector);
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
 
        // Nullability merged from table_info (notnull=1 → nullable=false)
        $this->assertFalse($schema['posts']['columns']['author_id']['nullable']);
    }
 
    public function testScanReturnsEmptySchemaWhenNoTables(): void
    {
        $connector = $this->makeConnector(['sqlite_master' => []]);
        $driver    = new SQLiteScannerDriver($connector);
 
        $this->assertEmpty($driver->scan());
    }
}
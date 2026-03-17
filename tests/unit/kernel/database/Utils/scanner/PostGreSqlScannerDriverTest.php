<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Connector\Utils\Scanner\PostGreScannerDriver;

class PostGreSqlScannerDriverTest extends TestCase
{
    private function makeConnector(array $fetchQueryMap = []): ConnectorInterface
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQueryOnce')->willReturn(['db' => 'public']);

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

        return $connector;
    }

    public function testGetTablesReturnsTableNames(): void
    {
        $connector = $this->makeConnector([
            'pg_tables' => [
                ['tablename' => 'users'],
                ['tablename' => 'posts'],
            ]
        ]);

        $driver = new PostGreScannerDriver($connector);
        $tables = $driver->getTables();

        $this->assertEquals(['users', 'posts'], $tables);
    }

    public function testGetTablesReturnsEmptyArrayWhenNoTables(): void
    {
        $connector = $this->makeConnector(['pg_tables' => []]);
        $driver    = new PostGreScannerDriver($connector);

        $this->assertEmpty($driver->getTables());
    }
    public function testGetColumnsNormalizesBasicTypes(): void
    {
        $connector = $this->makeConnector([
            'information_schema.columns' => [
                ['column_name' => 'id',    'data_type' => 'integer',          'is_nullable' => 'NO',  'column_default' => null],
                ['column_name' => 'email', 'data_type' => 'character varying', 'is_nullable' => 'NO',  'column_default' => null],
                ['column_name' => 'age',   'data_type' => 'integer',          'is_nullable' => 'YES', 'column_default' => null],
            ]
        ]);

        $driver  = new PostGreScannerDriver($connector);
        $columns = $driver->getColumns('users');

        $this->assertArrayHasKey('id', $columns);
        $this->assertEquals('int',    $columns['id']['type']);
        $this->assertFalse($columns['id']['nullable']);

        $this->assertArrayHasKey('email', $columns);
        $this->assertEquals('string', $columns['email']['type']);
        $this->assertFalse($columns['email']['nullable']);

        $this->assertArrayHasKey('age', $columns);
        $this->assertTrue($columns['age']['nullable']);
    }

    public function testGetColumnsDetectsSerialAsInt(): void
    {
        $connector = $this->makeConnector([
            'information_schema.columns' => [
                ['column_name' => 'id', 'data_type' => 'integer', 'is_nullable' => 'NO', 'column_default' => "nextval('users_id_seq'::regclass)"],
            ]
        ]);

        $driver  = new PostGreScannerDriver($connector);
        $columns = $driver->getColumns('users');

        $this->assertEquals('int', $columns['id']['type']);
    }

    public function testGetColumnsNormalizesBoolType(): void
    {
        $connector = $this->makeConnector([
            'information_schema.columns' => [
                ['column_name' => 'is_admin', 'data_type' => 'boolean', 'is_nullable' => 'NO', 'column_default' => null],
            ]
        ]);

        $driver  = new PostGreScannerDriver($connector);
        $columns = $driver->getColumns('users');

        $this->assertEquals('bool', $columns['is_admin']['type']);
    }

    public function testGetColumnsNormalizesJsonbType(): void
    {
        $connector = $this->makeConnector([
            'information_schema.columns' => [
                ['column_name' => 'tags', 'data_type' => 'jsonb', 'is_nullable' => 'YES', 'column_default' => null],
            ]
        ]);

        $driver  = new PostGreScannerDriver($connector);
        $columns = $driver->getColumns('users');

        $this->assertEquals('json', $columns['tags']['type']);
    }

    public function testGetColumnsReturnsEmptyArrayWhenNoColumns(): void
    {
        $connector = $this->makeConnector(['information_schema.columns' => []]);
        $driver    = new PostGreScannerDriver($connector);

        $this->assertEmpty($driver->getColumns('empty_table'));
    }

    // -------------------------------------------------------------------------
    // getPrimaryKeys
    // -------------------------------------------------------------------------

    public function testGetPrimaryKeysReturnsPKColumnNames(): void
    {
        $connector = $this->makeConnector([
            "constraint_type = 'PRIMARY KEY'" => [
                ['column_name' => 'id'],
            ]
        ]);

        $driver = new PostGreScannerDriver($connector);
        $pks    = $driver->getPrimaryKeys('users');

        $this->assertEquals(['id'], $pks);
    }

    public function testGetPrimaryKeysReturnsEmptyArrayWhenNoPK(): void
    {
        $connector = $this->makeConnector(["constraint_type = 'PRIMARY KEY'" => []]);
        $driver    = new PostGreScannerDriver($connector);

        $this->assertEmpty($driver->getPrimaryKeys('users'));
    }

    // -------------------------------------------------------------------------
    // getForeignKeys
    // -------------------------------------------------------------------------

    public function testGetForeignKeysNormalizesToFlatShape(): void
    {
        $connector = $this->makeConnector([
            "constraint_type = 'FOREIGN KEY'" => [
                [
                    'column_name'            => 'author_id',
                    'referenced_table_name'  => 'authors',
                    'delete_rule'            => 'CASCADE',
                    'update_rule'            => 'RESTRICT',
                ]
            ]
        ]);

        $driver = new PostGreScannerDriver($connector);
        $fks    = $driver->getForeignKeys('posts');

        $this->assertArrayHasKey('author_id', $fks);
        $this->assertEquals('int',      $fks['author_id']['type']);
        $this->assertEquals('authors',  $fks['author_id']['fk']);
        $this->assertEquals('CASCADE',  $fks['author_id']['onDelete']);
        $this->assertEquals('RESTRICT', $fks['author_id']['onUpdate']);
        $this->assertArrayNotHasKey('relation', $fks['author_id']);
    }

    public function testGetForeignKeysReturnsEmptyArrayWhenNoFK(): void
    {
        $connector = $this->makeConnector(["constraint_type = 'FOREIGN KEY'" => []]);
        $driver    = new PostGreScannerDriver($connector);

        $this->assertEmpty($driver->getForeignKeys('users'));
    }

    // -------------------------------------------------------------------------
    // getIndexes
    // -------------------------------------------------------------------------

    public function testGetIndexesReturnsIndexes(): void
    {
        $connector = $this->makeConnector([
            'ix.indisprimary' => [
                ['index_name' => 'idx_email', 'column_name' => 'email', 'is_unique' => 't'],
            ]
        ]);

        $driver  = new PostGreScannerDriver($connector);
        $indexes = $driver->getIndexes('users');

        $this->assertArrayHasKey('idx_email', $indexes);
        $this->assertTrue($indexes['idx_email']['unique']);
        $this->assertContains('email', $indexes['idx_email']['columns']);
    }

    public function testGetIndexesReturnsEmptyArrayWhenNoIndexes(): void
    {
        $connector = $this->makeConnector(['ix.indisprimary' => []]);
        $driver    = new PostGreScannerDriver($connector);

        $this->assertEmpty($driver->getIndexes('users'));
    }

    // -------------------------------------------------------------------------
    // scan
    // -------------------------------------------------------------------------

    public function testScanReturnsFlatSchemaWithFKOverride(): void
    {
        $connector = $this->makeConnector([
            'pg_tables' => [
                ['tablename' => 'posts'],
            ],
            "constraint_type = 'FOREIGN KEY'" => [
                ['column_name' => 'author_id', 'referenced_table_name' => 'authors', 'delete_rule' => 'CASCADE', 'update_rule' => 'RESTRICT'],
            ],
            "constraint_type = 'PRIMARY KEY'" => [
                ['column_name' => 'id'],
            ],
            'information_schema.columns' => [
                ['column_name' => 'id',        'data_type' => 'integer', 'is_nullable' => 'NO',  'column_default' => "nextval('posts_id_seq'::regclass)"],
                ['column_name' => 'author_id', 'data_type' => 'integer', 'is_nullable' => 'NO',  'column_default' => null],
            ],
            'ix.indisprimary' => [],
        ]);

        $driver = new PostGreScannerDriver($connector);
        $schema = $driver->scan();

        $this->assertArrayHasKey('posts', $schema);
        $this->assertArrayHasKey('columns', $schema['posts']);
        $this->assertArrayHasKey('primary_keys', $schema['posts']);
        $this->assertArrayHasKey('indexes', $schema['posts']);

        // FK column — flat shape with fk, onDelete, onUpdate, nullable merged
        $this->assertEquals('int',      $schema['posts']['columns']['author_id']['type']);
        $this->assertEquals('authors',  $schema['posts']['columns']['author_id']['fk']);
        $this->assertEquals('CASCADE',  $schema['posts']['columns']['author_id']['onDelete']);
        $this->assertEquals('RESTRICT', $schema['posts']['columns']['author_id']['onUpdate']);
        $this->assertFalse($schema['posts']['columns']['author_id']['nullable']);

        // Regular column unchanged
        $this->assertEquals('int', $schema['posts']['columns']['id']['type']);
    }

    public function testScanReturnsEmptySchemaWhenNoTables(): void
    {
        $connector = $this->makeConnector(['pg_tables' => []]);
        $driver    = new PostGreScannerDriver($connector);

        $this->assertEmpty($driver->scan());
    }
}

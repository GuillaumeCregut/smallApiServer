<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Connector\Utils\Scanner\MySQLScannerDriver;

class MySQLScannerDriverTest extends TestCase
{

    private function makeConnector(array $fetchQueryMap = []): ConnectorInterface
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQueryOnce')->willReturn(['db' => 'test_db']);
 
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
 
    // -------------------------------------------------------------------------
    // getTables
    // -------------------------------------------------------------------------
 
    public function testGetTablesReturnsTableNames(): void
    {
        $connector = $this->makeConnector([
            'SHOW TABLES' => [
                ['Tables_in_test_db' => 'users'],
                ['Tables_in_test_db' => 'posts'],
            ]
        ]);
 
        $this->assertEquals(['users', 'posts'], (new MySQLScannerDriver($connector))->getTables());
    }
 
    public function testGetTablesReturnsEmptyArrayWhenNoTables(): void
    {
        $connector = $this->makeConnector(['SHOW TABLES' => []]);
        $this->assertEmpty((new MySQLScannerDriver($connector))->getTables());
    }
 
    // -------------------------------------------------------------------------
    // getColumns — flat shape, no fk
    // -------------------------------------------------------------------------
 
    public function testGetColumnsNormalizesBasicTypes(): void
    {
        $connector = $this->makeConnector([
            'information_schema.columns' => [
                ['column_name' => 'id',    'data_type' => 'int',     'column_type' => 'int(11)',      'is_nullable' => 'NO'],
                ['column_name' => 'email', 'data_type' => 'varchar', 'column_type' => 'varchar(255)', 'is_nullable' => 'NO'],
                ['column_name' => 'age',   'data_type' => 'int',     'column_type' => 'int(11)',      'is_nullable' => 'YES'],
            ]
        ]);
 
        $columns = (new MySQLScannerDriver($connector))->getColumns('users');
 
        $this->assertEquals('int',    $columns['id']['type']);
        $this->assertFalse($columns['id']['nullable']);
        $this->assertEquals('string', $columns['email']['type']);
        $this->assertTrue($columns['age']['nullable']);
 
        foreach ($columns as $col) {
            $this->assertArrayNotHasKey('fk',       $col);
            $this->assertArrayNotHasKey('onDelete',  $col);
            $this->assertArrayNotHasKey('onUpdate',  $col);
        }
    }
 
    public function testGetColumnsNormalizesTinyint1AsBool(): void
    {
        $connector = $this->makeConnector([
            'information_schema.columns' => [
                ['column_name' => 'is_admin', 'data_type' => 'tinyint', 'column_type' => 'tinyint(1)', 'is_nullable' => 'NO'],
            ]
        ]);
 
        $columns = (new MySQLScannerDriver($connector))->getColumns('users');
        $this->assertEquals('bool', $columns['is_admin']['type']);
    }
 
    public function testGetColumnsNormalizesJsonType(): void
    {
        $connector = $this->makeConnector([
            'information_schema.columns' => [
                ['column_name' => 'tags', 'data_type' => 'json', 'column_type' => 'json', 'is_nullable' => 'YES'],
            ]
        ]);
 
        $columns = (new MySQLScannerDriver($connector))->getColumns('users');
        $this->assertEquals('json', $columns['tags']['type']);
    }
 
    public function testGetColumnsReturnsEmptyArrayWhenNoColumns(): void
    {
        $connector = $this->makeConnector(['information_schema.columns' => []]);
        $this->assertEmpty((new MySQLScannerDriver($connector))->getColumns('empty_table'));
    }
 
    // -------------------------------------------------------------------------
    // getForeignKeys — flat shape with fk, onDelete, onUpdate
    // -------------------------------------------------------------------------
 
    public function testGetForeignKeysNormalizesToFlatShape(): void
    {
        $connector = $this->makeConnector([
            'referential_constraints' => [
                [
                    'column_name'            => 'author_id',
                    'referenced_table_name'  => 'authors',
                    'delete_rule'            => 'CASCADE',
                    'update_rule'            => 'RESTRICT',
                ],
            ]
        ]);
 
        $fks = (new MySQLScannerDriver($connector))->getForeignKeys('posts');
 
        $this->assertArrayHasKey('author_id', $fks);
        $this->assertEquals('int',      $fks['author_id']['type']);
        $this->assertEquals('authors',  $fks['author_id']['fk']);
        $this->assertEquals('CASCADE',  $fks['author_id']['onDelete']);
        $this->assertEquals('RESTRICT', $fks['author_id']['onUpdate']);
        $this->assertArrayNotHasKey('relation', $fks['author_id']);
    }
 
    public function testGetForeignKeysReturnsEmptyArrayWhenNoFK(): void
    {
        $connector = $this->makeConnector(['referential_constraints' => []]);
        $this->assertEmpty((new MySQLScannerDriver($connector))->getForeignKeys('users'));
    }
 
    // -------------------------------------------------------------------------
    // getPrimaryKeys
    // -------------------------------------------------------------------------
 
    public function testGetPrimaryKeysReturnsPKColumnNames(): void
    {
        $connector = $this->makeConnector([
            "constraint_name = 'PRIMARY'" => [['column_name' => 'id']]
        ]);
 
        $this->assertEquals(['id'], (new MySQLScannerDriver($connector))->getPrimaryKeys('users'));
    }
 
    public function testGetPrimaryKeysReturnsEmptyArrayWhenNoPK(): void
    {
        $connector = $this->makeConnector(["constraint_name = 'PRIMARY'" => []]);
        $this->assertEmpty((new MySQLScannerDriver($connector))->getPrimaryKeys('users'));
    }
 
    // -------------------------------------------------------------------------
    // getIndexes
    // -------------------------------------------------------------------------
 
    public function testGetIndexesReturnsIndexes(): void
    {
        $connector = $this->makeConnector([
            'information_schema.statistics' => [
                ['index_name' => 'idx_email', 'column_name' => 'email', 'non_unique' => 0],
            ]
        ]);
 
        $indexes = (new MySQLScannerDriver($connector))->getIndexes('users');
        $this->assertArrayHasKey('idx_email', $indexes);
        $this->assertTrue($indexes['idx_email']['unique']);
        $this->assertContains('email', $indexes['idx_email']['columns']);
    }
 
    public function testGetIndexesReturnsEmptyArrayWhenNoIndexes(): void
    {
        $connector = $this->makeConnector(['information_schema.statistics' => []]);
        $this->assertEmpty((new MySQLScannerDriver($connector))->getIndexes('users'));
    }
 
    // -------------------------------------------------------------------------
    // scan
    // -------------------------------------------------------------------------
 
    public function testScanReturnsFlatSchemaWithFKConstraint(): void
    {
        $connector = $this->makeConnector([
            'SHOW TABLES'              => [['Tables_in_test_db' => 'posts']],
            'referential_constraints'  => [
                ['column_name' => 'author_id', 'referenced_table_name' => 'authors', 'delete_rule' => 'CASCADE', 'update_rule' => 'RESTRICT'],
            ],
            "constraint_name = 'PRIMARY'" => [['column_name' => 'id']],
            'information_schema.columns'  => [
                ['column_name' => 'id',        'data_type' => 'int', 'column_type' => 'int(11)', 'is_nullable' => 'NO'],
                ['column_name' => 'author_id', 'data_type' => 'int', 'column_type' => 'int(11)', 'is_nullable' => 'NO'],
            ],
            'information_schema.statistics' => [],
        ]);
 
        $schema = (new MySQLScannerDriver($connector))->scan();
 
        $this->assertArrayHasKey('posts', $schema);
 
        // Regular column — no fk keys
        $this->assertEquals('int', $schema['posts']['columns']['id']['type']);
        $this->assertArrayNotHasKey('fk',       $schema['posts']['columns']['id']);
        $this->assertArrayNotHasKey('onDelete',  $schema['posts']['columns']['id']);
 
        // FK column — flat shape with fk, onDelete, onUpdate, nullable merged
        $this->assertEquals('int',      $schema['posts']['columns']['author_id']['type']);
        $this->assertEquals('authors',  $schema['posts']['columns']['author_id']['fk']);
        $this->assertEquals('CASCADE',  $schema['posts']['columns']['author_id']['onDelete']);
        $this->assertEquals('RESTRICT', $schema['posts']['columns']['author_id']['onUpdate']);
        $this->assertFalse($schema['posts']['columns']['author_id']['nullable']);
 
        $this->assertContains('id', $schema['posts']['primary_keys']);
    }
 
    public function testScanReturnsFlatSchemaWithNoConstraint(): void
    {
        // FK column exists in DB but no constraint — onDelete/onUpdate are null
        $connector = $this->makeConnector([
            'SHOW TABLES'                   => [['Tables_in_test_db' => 'posts']],
            'referential_constraints'       => [],
            "constraint_name = 'PRIMARY'"   => [['column_name' => 'id']],
            'information_schema.columns'    => [
                ['column_name' => 'id',        'data_type' => 'int', 'column_type' => 'int(11)', 'is_nullable' => 'NO'],
                ['column_name' => 'author_id', 'data_type' => 'int', 'column_type' => 'int(11)', 'is_nullable' => 'NO'],
            ],
            'information_schema.statistics' => [],
        ]);
 
        $schema = (new MySQLScannerDriver($connector))->scan();
 
        // author_id is plain int — no fk metadata since no constraint exists
        $this->assertEquals('int', $schema['posts']['columns']['author_id']['type']);
        $this->assertArrayNotHasKey('fk',      $schema['posts']['columns']['author_id']);
        $this->assertArrayNotHasKey('onDelete', $schema['posts']['columns']['author_id']);
    }
 
    public function testScanReturnsEmptySchemaWhenNoTables(): void
    {
        $connector = $this->makeConnector(['SHOW TABLES' => []]);
        $this->assertEmpty((new MySQLScannerDriver($connector))->scan());
    }
}

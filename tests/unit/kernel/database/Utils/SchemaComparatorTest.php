<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Utils\SchemaComparator;

class SchemaComparatorTest extends TestCase
{
    private SchemaComparator $comparator;
 
    protected function setUp(): void
    {
        $this->comparator = new SchemaComparator();
    }
 
    private function makeSchema(array $tables): array
    {
        $schema = [];
        foreach ($tables as $table => $columns) {
            $schema[$table] = ['columns' => $columns, 'primary_keys' => [], 'indexes' => []];
        }
        return $schema;
    }
 
    private function emptyDiff(): array
    {
        return [
            'tables_to_create'   => [],
            'tables_to_drop'     => [],
            'columns_to_add'     => [],
            'columns_to_drop'    => [],
            'columns_to_alter'   => [],
            'constraints_to_add' => [],
        ];
    }
 
    // -------------------------------------------------------------------------
    // isInSync
    // -------------------------------------------------------------------------
 
    public function testIsInSyncWhenNoDiff(): void
    {
        $this->assertTrue($this->comparator->isInSync($this->emptyDiff()));
    }
 
    public function testIsNotInSyncWhenTableToCreate(): void
    {
        $diff = array_merge($this->emptyDiff(), ['tables_to_create' => ['users' => []]]);
        $this->assertFalse($this->comparator->isInSync($diff));
    }
 
    public function testIsNotInSyncWhenConstraintToAdd(): void
    {
        $diff = array_merge($this->emptyDiff(), ['constraints_to_add' => ['users' => ['author_id' => []]]]);
        $this->assertFalse($this->comparator->isInSync($diff));
    }
 
    // -------------------------------------------------------------------------
    // tables_to_create / tables_to_drop
    // -------------------------------------------------------------------------
 
    public function testDetectsTableToCreate(): void
    {
        $entitySchema = $this->makeSchema([
            'users' => ['id' => ['nullable' => false, 'type' => 'int']]
        ]);
 
        $diff = $this->comparator->compare($entitySchema, []);
 
        $this->assertArrayHasKey('users', $diff['tables_to_create']);
        $this->assertEmpty($diff['tables_to_drop']);
    }
 
    public function testDetectsTableToDrop(): void
    {
        $dbSchema = $this->makeSchema([
            'users' => ['id' => ['nullable' => false, 'type' => 'int']]
        ]);
 
        $diff = $this->comparator->compare([], $dbSchema);
 
        $this->assertContains('users', $diff['tables_to_drop']);
        $this->assertEmpty($diff['tables_to_create']);
    }
 
    // -------------------------------------------------------------------------
    // columns_to_add / columns_to_drop
    // -------------------------------------------------------------------------
 
    public function testDetectsColumnToAdd(): void
    {
        $entitySchema = $this->makeSchema([
            'users' => [
                'id'    => ['nullable' => false, 'type' => 'int'],
                'phone' => ['nullable' => true,  'type' => 'string'],
            ]
        ]);
        $dbSchema = $this->makeSchema([
            'users' => ['id' => ['nullable' => false, 'type' => 'int']]
        ]);
 
        $diff = $this->comparator->compare($entitySchema, $dbSchema);
 
        $this->assertArrayHasKey('phone', $diff['columns_to_add']['users']);
        $this->assertTrue($diff['columns_to_add']['users']['phone']['nullable']);
    }
 
    public function testDetectsFKColumnToAdd(): void
    {
        $entitySchema = $this->makeSchema([
            'posts' => [
                'id'        => ['nullable' => false, 'type' => 'int'],
                'author_id' => ['nullable' => false, 'type' => 'int', 'fk' => 'authors', 'onDelete' => 'CASCADE', 'onUpdate' => 'RESTRICT'],
            ]
        ]);
        $dbSchema = $this->makeSchema([
            'posts' => ['id' => ['nullable' => false, 'type' => 'int']]
        ]);
 
        $diff = $this->comparator->compare($entitySchema, $dbSchema);
 
        $this->assertArrayHasKey('author_id', $diff['columns_to_add']['posts']);
        $this->assertEquals('authors',  $diff['columns_to_add']['posts']['author_id']['fk']);
        $this->assertEquals('CASCADE',  $diff['columns_to_add']['posts']['author_id']['onDelete']);
        $this->assertEquals('RESTRICT', $diff['columns_to_add']['posts']['author_id']['onUpdate']);
    }
 
    public function testDetectsColumnToDrop(): void
    {
        $entitySchema = $this->makeSchema([
            'users' => ['id' => ['nullable' => false, 'type' => 'int']]
        ]);
        $dbSchema = $this->makeSchema([
            'users' => [
                'id'       => ['nullable' => false, 'type' => 'int'],
                'obsolete' => ['nullable' => true,  'type' => 'string'],
            ]
        ]);
 
        $diff = $this->comparator->compare($entitySchema, $dbSchema);
 
        $this->assertContains('obsolete', $diff['columns_to_drop']['users']);
    }
 
    // -------------------------------------------------------------------------
    // columns_to_alter
    // -------------------------------------------------------------------------
 
    public function testDetectsTypeChange(): void
    {
        $entitySchema = $this->makeSchema(['users' => ['age' => ['nullable' => false, 'type' => 'float']]]);
        $dbSchema     = $this->makeSchema(['users' => ['age' => ['nullable' => false, 'type' => 'int']]]);
 
        $diff = $this->comparator->compare($entitySchema, $dbSchema);
 
        $this->assertEquals('int',   $diff['columns_to_alter']['users']['age']['type']['from']);
        $this->assertEquals('float', $diff['columns_to_alter']['users']['age']['type']['to']);
    }
 
    public function testDetectsNullabilityChange(): void
    {
        $entitySchema = $this->makeSchema(['users' => ['email' => ['nullable' => true,  'type' => 'string']]]);
        $dbSchema     = $this->makeSchema(['users' => ['email' => ['nullable' => false, 'type' => 'string']]]);
 
        $diff = $this->comparator->compare($entitySchema, $dbSchema);
 
        $this->assertFalse($diff['columns_to_alter']['users']['email']['nullable']['from']);
        $this->assertTrue($diff['columns_to_alter']['users']['email']['nullable']['to']);
    }
 
    public function testFkValueDifferenceDoesNotTriggerAlter(): void
    {
        $entitySchema = $this->makeSchema([
            'posts' => ['author_id' => ['nullable' => false, 'type' => 'int', 'fk' => 'authors', 'onDelete' => 'CASCADE', 'onUpdate' => 'RESTRICT']]
        ]);
        $dbSchema = $this->makeSchema([
            'posts' => ['author_id' => ['nullable' => false, 'type' => 'int', 'fk' => 'authors', 'onDelete' => 'CASCADE', 'onUpdate' => 'RESTRICT']]
        ]);
 
        $diff = $this->comparator->compare($entitySchema, $dbSchema);
 
        $this->assertEmpty($diff['columns_to_alter']);
    }
 
    // -------------------------------------------------------------------------
    // constraints_to_add
    // -------------------------------------------------------------------------
 
    public function testDetectsMissingConstraint(): void
    {
        $entitySchema = $this->makeSchema([
            'posts' => [
                'id'        => ['nullable' => false, 'type' => 'int'],
                'author_id' => ['nullable' => false, 'type' => 'int', 'fk' => 'authors', 'onDelete' => 'CASCADE', 'onUpdate' => 'RESTRICT'],
            ]
        ]);
        // DB has the column but no constraint (no onDelete/onUpdate)
        $dbSchema = $this->makeSchema([
            'posts' => [
                'id'        => ['nullable' => false, 'type' => 'int'],
                'author_id' => ['nullable' => false, 'type' => 'int'],
            ]
        ]);
 
        $diff = $this->comparator->compare($entitySchema, $dbSchema);
 
        $this->assertArrayHasKey('posts', $diff['constraints_to_add']);
        $this->assertArrayHasKey('author_id', $diff['constraints_to_add']['posts']);
        $this->assertEquals('authors',  $diff['constraints_to_add']['posts']['author_id']['fk']);
        $this->assertEquals('CASCADE',  $diff['constraints_to_add']['posts']['author_id']['onDelete']);
        $this->assertEquals('RESTRICT', $diff['constraints_to_add']['posts']['author_id']['onUpdate']);
    }
 
    public function testNoConstraintAddedWhenConstraintAlreadyExists(): void
    {
        $entitySchema = $this->makeSchema([
            'posts' => [
                'author_id' => ['nullable' => false, 'type' => 'int', 'fk' => 'authors', 'onDelete' => 'CASCADE', 'onUpdate' => 'RESTRICT'],
            ]
        ]);
        // DB already has constraint
        $dbSchema = $this->makeSchema([
            'posts' => [
                'author_id' => ['nullable' => false, 'type' => 'int', 'fk' => 'authors', 'onDelete' => 'CASCADE', 'onUpdate' => 'RESTRICT'],
            ]
        ]);
 
        $diff = $this->comparator->compare($entitySchema, $dbSchema);
 
        $this->assertEmpty($diff['constraints_to_add']);
    }
 
    public function testNoConstraintAddedForNonFKColumn(): void
    {
        $entitySchema = $this->makeSchema([
            'users' => ['email' => ['nullable' => false, 'type' => 'string']]
        ]);
        $dbSchema = $this->makeSchema([
            'users' => ['email' => ['nullable' => false, 'type' => 'string']]
        ]);
 
        $diff = $this->comparator->compare($entitySchema, $dbSchema);
 
        $this->assertEmpty($diff['constraints_to_add']);
    }
 
    public function testConstraintToAddMakesIsInSyncReturnFalse(): void
    {
        $entitySchema = $this->makeSchema([
            'posts' => [
                'author_id' => ['nullable' => false, 'type' => 'int', 'fk' => 'authors', 'onDelete' => 'CASCADE', 'onUpdate' => 'RESTRICT'],
            ]
        ]);
        $dbSchema = $this->makeSchema([
            'posts' => [
                'author_id' => ['nullable' => false, 'type' => 'int'],
            ]
        ]);
 
        $diff = $this->comparator->compare($entitySchema, $dbSchema);
 
        $this->assertFalse($this->comparator->isInSync($diff));
    }
 
    // -------------------------------------------------------------------------
    // In sync
    // -------------------------------------------------------------------------
 
    public function testNoChangesWhenSchemasMatch(): void
    {
        $columns = [
            'id'        => ['nullable' => false, 'type' => 'int'],
            'email'     => ['nullable' => false, 'type' => 'string'],
            'author_id' => ['nullable' => false, 'type' => 'int', 'fk' => 'authors', 'onDelete' => 'CASCADE', 'onUpdate' => 'RESTRICT'],
        ];
 
        $schema = $this->makeSchema(['users' => $columns]);
 
        $this->assertTrue($this->comparator->isInSync(
            $this->comparator->compare($schema, $schema)
        ));
    }
 
    public function testMultipleTablesPartiallyInSync(): void
    {
        $entitySchema = $this->makeSchema([
            'users' => ['id' => ['nullable' => false, 'type' => 'int']],
            'posts' => ['id' => ['nullable' => false, 'type' => 'int']],
        ]);
        $dbSchema = $this->makeSchema([
            'users' => ['id' => ['nullable' => false, 'type' => 'int']],
        ]);
 
        $diff = $this->comparator->compare($entitySchema, $dbSchema);
 
        $this->assertFalse($this->comparator->isInSync($diff));
        $this->assertArrayHasKey('posts', $diff['tables_to_create']);
        $this->assertEmpty($diff['columns_to_add']);
        $this->assertEmpty($diff['columns_to_alter']);
        $this->assertEmpty($diff['constraints_to_add']);
    }
}

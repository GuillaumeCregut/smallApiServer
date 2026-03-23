<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Utils\PivotSchemaComparator;
use App\Kernel\Connector\Interfaces\DatabaseScannerDriverInterface;

class PivotSchemaComparatorTest extends TestCase
{
    private function makePivot(
        string $table = 'articles_tags',
        string $ownerCol  = 'article_id',
        string $targetCol = 'tag_id',
        string $ownerTable  = 'articles',
        string $targetTable = 'tags'
    ): array {
        return [
            'pivotTable' => $table,
            'ownerTable' => $ownerTable,
            'targetTable' => $targetTable,
            'ownerCol' => $ownerCol,
            'targetCol' => $targetCol,
        ];
    }

    private function emptyDiff(): array
    {
        return [
            'pivot_tables_to_create' => [],
            'pivot_tables_to_drop' => [],
            'pivot_tables_to_fix' => [],
        ];
    }

    public function testIsInSyncWhenNoDiff(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $comparator = new PivotSchemaComparator($driver);

        $this->assertTrue($comparator->isInSync($this->emptyDiff()));
    }

    public function testIsNotInSyncWhenTableToCreate(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $comparator = new PivotSchemaComparator($driver);
        $diff = array_merge($this->emptyDiff(), [
            'pivot_tables_to_create' => [$this->makePivot()]
        ]);

        $this->assertFalse($comparator->isInSync($diff));
    }

    public function testIsNotInSyncWhenTableToDrop(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $comparator = new PivotSchemaComparator($driver);
        $diff = array_merge($this->emptyDiff(), [
            'pivot_tables_to_drop' => ['articles_tags']
        ]);

        $this->assertFalse($comparator->isInSync($diff));
    }

    public function testIsNotInSyncWhenTableToFix(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $comparator = new PivotSchemaComparator($driver);
        $diff       = array_merge($this->emptyDiff(), [
            'pivot_tables_to_fix' => ['articles_tags' => ['missing_columns' => ['tag_id']]]
        ]);

        $this->assertFalse($comparator->isInSync($diff));
    }

    public function testDetectsTableToCreateWhenAbsent(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $comparator = new PivotSchemaComparator($driver);
        $pivot      = $this->makePivot();

        $scanResult = [
            'articles_tags' => [
                'exists' => false,
                'columns_ok' => false,
                'missing_columns' => ['article_id', 'tag_id'],
                'extra_columns' => []
            ]
        ];

        $diff = $comparator->compare([$pivot], $scanResult, []);

        $this->assertCount(1, $diff['pivot_tables_to_create']);
        $this->assertSame($pivot, $diff['pivot_tables_to_create'][0]);
    }

    public function testNoTableToCreateWhenPivotExists(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $driver->method('getColumns')->willReturn([]);
        $driver->method('getForeignKeys')->willReturn([]);
        $comparator = new PivotSchemaComparator($driver);

        $scanResult = [
            'articles_tags' => [
                'exists' => true,
                'columns_ok' => true,
                'missing_columns' => [],
                'extra_columns' => []
            ]
        ];

        $diff = $comparator->compare([$this->makePivot()], $scanResult, []);

        $this->assertEmpty($diff['pivot_tables_to_create']);
    }

    public function testDetectsTableToFixWhenColumnsMissing(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $driver->method('getColumns')->willReturn([]);
        $driver->method('getForeignKeys')->willReturn([]);
        $comparator = new PivotSchemaComparator($driver);

        $scanResult = [
            'articles_tags' => [
                'exists' => true,
                'columns_ok' => false,
                'missing_columns' => ['tag_id'],
                'extra_columns' => []
            ]
        ];

        $diff = $comparator->compare([$this->makePivot()], $scanResult, []);

        $this->assertArrayHasKey('articles_tags', $diff['pivot_tables_to_fix']);
        $this->assertContains('tag_id', $diff['pivot_tables_to_fix']['articles_tags']['missing_columns']);
    }

    public function testNoTableToFixWhenColumnsOk(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $driver->method('getColumns')->willReturn([]);
        $driver->method('getForeignKeys')->willReturn([]);
        $comparator = new PivotSchemaComparator($driver);

        $scanResult = [
            'articles_tags' => [
                'exists' => true,
                'columns_ok' => true,
                'missing_columns' => [],
                'extra_columns' => []
            ]
        ];

        $diff = $comparator->compare([$this->makePivot()], $scanResult, []);

        $this->assertEmpty($diff['pivot_tables_to_fix']);
    }

    public function testDetectsOrphanPivotTableToDrop(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $driver->method('getColumns')->willReturn([
            'entity_a_id' => ['nullable' => false, 'type' => 'int'],
            'entity_b_id' => ['nullable' => false, 'type' => 'int'],
        ]);
        $driver->method('getForeignKeys')->willReturn([
            'entity_a_id' => ['type' => 'int', 'fk' => 'entity_as'],
            'entity_b_id' => ['type' => 'int', 'fk' => 'entity_bs'],
        ]);

        $comparator = new PivotSchemaComparator($driver);

        $diff = $comparator->compare(
            [],
            [],
            ['entity_as_entity_bs']
        );

        $this->assertContains('entity_as_entity_bs', $diff['pivot_tables_to_drop']);
    }

    public function testDoesNotDropKnownPivotTable(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $driver->method('getColumns')->willReturn([
            'article_id' => ['nullable' => false, 'type' => 'int'],
            'tag_id'     => ['nullable' => false, 'type' => 'int'],
        ]);
        $driver->method('getForeignKeys')->willReturn([
            'article_id' => ['type' => 'int', 'fk' => 'articles'],
            'tag_id'     => ['type' => 'int', 'fk' => 'tags'],
        ]);

        $comparator = new PivotSchemaComparator($driver);
        $pivot      = $this->makePivot();
        $scanResult = [
            'articles_tags' => [
                'exists' => true,
                'columns_ok' => true,
                'missing_columns' => [],
                'extra_columns' => []
            ]
        ];

        $diff = $comparator->compare([$pivot], $scanResult, ['articles_tags']);

        $this->assertNotContains('articles_tags', $diff['pivot_tables_to_drop']);
    }

    public function testDoesNotDropMigrationsTable(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $comparator = new PivotSchemaComparator($driver);

        $diff = $comparator->compare([], [], ['migrations']);

        $this->assertNotContains('migrations', $diff['pivot_tables_to_drop']);
    }

    public function testDoesNotDropTableWithMoreThanTwoColumns(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $driver->method('getColumns')->willReturn([
            'entity_a_id' => ['nullable' => false, 'type' => 'int'],
            'entity_b_id' => ['nullable' => false, 'type' => 'int'],
            'extra_col'   => ['nullable' => true,  'type' => 'string'],
        ]);
        $driver->method('getForeignKeys')->willReturn([
            'entity_a_id' => ['type' => 'int', 'fk' => 'entity_as'],
            'entity_b_id' => ['type' => 'int', 'fk' => 'entity_bs'],
        ]);

        $comparator = new PivotSchemaComparator($driver);

        $diff = $comparator->compare([], [], ['some_table']);

        $this->assertNotContains('some_table', $diff['pivot_tables_to_drop']);
    }

    public function testDoesNotDropTableWithOnlyOneFk(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $driver->method('getColumns')->willReturn([
            'entity_a_id' => ['nullable' => false, 'type' => 'int'],
            'name'        => ['nullable' => false, 'type' => 'string'],
        ]);
        $driver->method('getForeignKeys')->willReturn([
            'entity_a_id' => ['type' => 'int', 'fk' => 'entity_as'],
        ]);

        $comparator = new PivotSchemaComparator($driver);

        $diff = $comparator->compare([], [], ['some_table']);

        $this->assertNotContains('some_table', $diff['pivot_tables_to_drop']);
    }

    public function testReturnsEmptyDiffOnEmptyInputs(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $comparator = new PivotSchemaComparator($driver);
 
        $diff = $comparator->compare([], [], []);
 
        $this->assertSame($this->emptyDiff(), $diff);
        $this->assertTrue($comparator->isInSync($diff));
    }
}

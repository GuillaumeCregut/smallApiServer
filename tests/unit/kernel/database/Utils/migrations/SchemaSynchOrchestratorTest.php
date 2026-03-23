<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Utils\SchemaSyncOrchestrator;
use App\Kernel\Connector\Utils\Scanner\AbstractScannerDriver;

class SchemaSynchOrchestratorTest extends TestCase
{
    private function makeDriver(
        array $tables      = [],
        array $columns     = [],
        array $foreignKeys = [],
        array $primaryKeys = [],
        array $indexes     = []
    ): AbstractScannerDriver {
        $driver = $this->createStub(AbstractScannerDriver::class);
        $driver->method('getTables')->willReturn($tables);
        $driver->method('getColumns')->willReturn($columns);
        $driver->method('getForeignKeys')->willReturn($foreignKeys);
        $driver->method('getPrimaryKeys')->willReturn($primaryKeys);
        $driver->method('getIndexes')->willReturn($indexes);
        $driver->method('tableExists')->willReturn(false);
        $driver->method('scan')->willReturn([]);
        return $driver;
    }

    private function makeSchema(array $tables): array
    {
        $schema = [];
        foreach ($tables as $table => $columns) {
            $schema[$table] = ['columns' => $columns, 'primary_keys' => [], 'indexes' => []];
        }
        return $schema;
    }

    private function makePivot(
        string $table     = 'articles_tags',
        string $ownerCol  = 'article_id',
        string $targetCol = 'tag_id'
    ): array {
        return [
            'pivotTable'  => $table,
            'ownerTable'  => 'articles',
            'targetTable' => 'tags',
            'ownerCol'    => $ownerCol,
            'targetCol'   => $targetCol,
        ];
    }

    public function testRunReturnsEntitiesAndPivotsKeys(): void
    {
        $driver = $this->makeDriver();
        $orchestrator = new SchemaSyncOrchestrator($driver);
 
        $result = $orchestrator->run([], []);
 
        $this->assertArrayHasKey('entities', $result);
        $this->assertArrayHasKey('pivots',   $result);
    }

    public function testIsInSyncOnEmptyInputs(): void
    {
        $driver       = $this->makeDriver();
        $orchestrator = new SchemaSyncOrchestrator($driver);
 
        $result = $orchestrator->run([], []);
 
        $this->assertTrue($orchestrator->isInSync($result));
    }

    public function testDetectsEntityTableToCreate(): void
    {
        $driver = $this->createStub(AbstractScannerDriver::class);
        $driver->method('getTables')->willReturn([]);
        $driver->method('getColumns')->willReturn([]);
        $driver->method('getForeignKeys')->willReturn([]);
        $driver->method('getPrimaryKeys')->willReturn([]);
        $driver->method('getIndexes')->willReturn([]);
        $driver->method('tableExists')->willReturn(false);
        $driver->method('scan')->willReturn([]);
 
        $orchestrator = new SchemaSyncOrchestrator($driver);
        $entitySchema = $this->makeSchema([
            'users' => ['id' => ['nullable' => false, 'type' => 'int']]
        ]);
 
        $result = $orchestrator->run($entitySchema, []);
 
        $this->assertArrayHasKey('users', $result['entities']['tables_to_create']);
        $this->assertFalse($orchestrator->isInSync($result));
    }

    public function testDetectsEntityTableToDrop(): void
    {
        $driver = $this->createStub(AbstractScannerDriver::class);
        $driver->method('getTables')->willReturn([]);
        $driver->method('getColumns')->willReturn([]);
        $driver->method('getForeignKeys')->willReturn([]);
        $driver->method('getPrimaryKeys')->willReturn([]);
        $driver->method('getIndexes')->willReturn([]);
        $driver->method('tableExists')->willReturn(false);
        $driver->method('scan')->willReturn(
            $this->makeSchema([
                'users' => ['id' => ['nullable' => false, 'type' => 'int']]
            ])
        );
 
        $orchestrator = new SchemaSyncOrchestrator($driver);
 
        $result = $orchestrator->run([], []);
 
        $this->assertContains('users', $result['entities']['tables_to_drop']);
        $this->assertFalse($orchestrator->isInSync($result));
    }

    public function testDetectsPivotTableToCreate(): void
    {
         $driver = $this->createStub(AbstractScannerDriver::class);
        $driver->method('getTables')->willReturn([]);
        $driver->method('getColumns')->willReturn([]);
        $driver->method('getForeignKeys')->willReturn([]);
        $driver->method('getPrimaryKeys')->willReturn([]);
        $driver->method('getIndexes')->willReturn([]);
        $driver->method('tableExists')->willReturn(false);
        $driver->method('scan')->willReturn([]);
 
        $orchestrator = new SchemaSyncOrchestrator($driver);
        $pivot        = $this->makePivot();
 
        $result = $orchestrator->run([], [$pivot]);
 
        $this->assertCount(1, $result['pivots']['pivot_tables_to_create']);
        $this->assertSame($pivot, $result['pivots']['pivot_tables_to_create'][0]);
        $this->assertFalse($orchestrator->isInSync($result));
    }

    public function testPivotTableExcludedFromEntityScan(): void
    {
        $driver = $this->createMock(AbstractScannerDriver::class);
        $driver->method('getTables')->willReturn(['articles_tags']);
        $driver->method('tableExists')->willReturn(true);
        $driver->method('getColumns')->willReturn([
            'article_id' => ['nullable' => false, 'type' => 'int'],
            'tag_id'     => ['nullable' => false, 'type' => 'int'],
        ]);
        $driver->method('getForeignKeys')->willReturn([]);
        $driver->method('getPrimaryKeys')->willReturn([]);
        $driver->method('getIndexes')->willReturn([]);
        $driver->method('scan')->willReturn([]);
 
        $driver->expects($this->once())
            ->method('excludeTables')
            ->with($this->containsEqual('articles_tags'));
 
        $orchestrator = new SchemaSyncOrchestrator($driver);
        $orchestrator->run([], [$this->makePivot()]);
    }

    public function testAllDbTablesPassedToPivotComparatorBeforeExclusion(): void
    {
        $driver = $this->createStub(AbstractScannerDriver::class);
 
        $callOrder = [];
        $driver->method('getTables')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'getTables';
                return ['articles_tags', 'users'];
            });
        $driver->method('excludeTables')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'excludeTables';
            });
        $driver->method('tableExists')->willReturn(true);
        $driver->method('getColumns')->willReturn([
            'article_id' => ['nullable' => false, 'type' => 'int'],
            'tag_id'     => ['nullable' => false, 'type' => 'int'],
        ]);
        $driver->method('getForeignKeys')->willReturn([]);
        $driver->method('getPrimaryKeys')->willReturn([]);
        $driver->method('getIndexes')->willReturn([]);
        $driver->method('scan')->willReturn([]);
 
        $orchestrator = new SchemaSyncOrchestrator($driver);
        $orchestrator->run([], [$this->makePivot()]);
 
        $getTablesPos    = array_search('getTables',    $callOrder);
        $excludeTablesPos = array_search('excludeTables', $callOrder);
 
        $this->assertLessThan($excludeTablesPos, $getTablesPos);
    }

    public function testIsInSyncWhenEntitiesAndPivotsMatch(): void
    {
        $entitySchema = $this->makeSchema([
            'users' => ['id' => ['nullable' => false, 'type' => 'int']]
        ]);
        $pivot = $this->makePivot();
 
        $driver = $this->createStub(AbstractScannerDriver::class);
        $driver->method('getTables')->willReturn(['articles_tags']);
        $driver->method('scan')->willReturn($entitySchema);
        $driver->method('tableExists')->willReturn(true);
        $driver->method('getColumns')->willReturn([
            'article_id' => ['nullable' => false, 'type' => 'int'],
            'tag_id'     => ['nullable' => false, 'type' => 'int'],
        ]);
        $driver->method('getForeignKeys')->willReturn([]);
        $driver->method('getPrimaryKeys')->willReturn([]);
        $driver->method('getIndexes')->willReturn([]);
 
        $orchestrator = new SchemaSyncOrchestrator($driver);
        $result       = $orchestrator->run($entitySchema, [$pivot]);
 
        $this->assertTrue($orchestrator->isInSync($result));
    }
}
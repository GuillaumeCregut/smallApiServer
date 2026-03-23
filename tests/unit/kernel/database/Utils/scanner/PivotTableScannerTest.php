<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Utils\Scanner\PivotTableScanner;
use App\Kernel\Connector\Interfaces\DatabaseScannerDriverInterface;

class PivotTableScannerTest extends TestCase
{
    private function makePivot(
        string $table    = 'articles_tags',
        string $ownerCol = 'article_id',
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

    public function testScanReturnsNotExistsWhenTableMissing(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $driver->method('tableExists')->willReturn(false);

        $scanner = new PivotTableScanner($driver);
        $result  = $scanner->scan([$this->makePivot()]);

        $this->assertArrayHasKey('articles_tags', $result);
        $this->assertFalse($result['articles_tags']['exists']);
        $this->assertFalse($result['articles_tags']['columns_ok']);
    }

    public function testScanReportsMissingColumnsWhenTableAbsent(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $driver->method('tableExists')->willReturn(false);

        $scanner = new PivotTableScanner($driver);
        $result  = $scanner->scan([$this->makePivot()]);

        $this->assertContains('article_id', $result['articles_tags']['missing_columns']);
        $this->assertContains('tag_id',     $result['articles_tags']['missing_columns']);
        $this->assertEmpty($result['articles_tags']['extra_columns']);
    }

    public function testScanReturnsColumnsOkWhenTableAndColumnsMatch(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $driver->method('tableExists')->willReturn(true);
        $driver->method('getColumns')->willReturn([
            'article_id' => ['nullable' => false, 'type' => 'int'],
            'tag_id'     => ['nullable' => false, 'type' => 'int'],
        ]);

        $scanner = new PivotTableScanner($driver);
        $result  = $scanner->scan([$this->makePivot()]);

        $this->assertTrue($result['articles_tags']['exists']);
        $this->assertTrue($result['articles_tags']['columns_ok']);
        $this->assertEmpty($result['articles_tags']['missing_columns']);
        $this->assertEmpty($result['articles_tags']['extra_columns']);
    }

    public function testScanReportsColumnsNotOkWhenColumnMissing(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $driver->method('tableExists')->willReturn(true);
        $driver->method('getColumns')->willReturn([
            'article_id' => ['nullable' => false, 'type' => 'int'],
            // tag_id manquant
        ]);

        $scanner = new PivotTableScanner($driver);
        $result  = $scanner->scan([$this->makePivot()]);

        $this->assertTrue($result['articles_tags']['exists']);
        $this->assertFalse($result['articles_tags']['columns_ok']);
        $this->assertContains('tag_id', $result['articles_tags']['missing_columns']);
    }

    public function testScanReportsExtraColumns(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $driver->method('tableExists')->willReturn(true);
        $driver->method('getColumns')->willReturn([
            'article_id' => ['nullable' => false, 'type' => 'int'],
            'tag_id'     => ['nullable' => false, 'type' => 'int'],
            'extra_col'  => ['nullable' => true,  'type' => 'string'],
        ]);

        $scanner = new PivotTableScanner($driver);
        $result  = $scanner->scan([$this->makePivot()]);

        $this->assertTrue($result['articles_tags']['columns_ok']);
        $this->assertContains('extra_col', $result['articles_tags']['extra_columns']);
    }

    public function testScanHandlesMultiplePivots(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $driver->method('tableExists')
            ->willReturnCallback(fn($table) => $table === 'articles_tags');
        $driver->method('getColumns')->willReturn([
            'article_id' => ['nullable' => false, 'type' => 'int'],
            'tag_id'     => ['nullable' => false, 'type' => 'int'],
        ]);

        $scanner = new PivotTableScanner($driver);
        $result  = $scanner->scan([
            $this->makePivot('articles_tags', 'article_id', 'tag_id'),
            $this->makePivot('courses_schools', 'course_id', 'school_id'),
        ]);

        $this->assertTrue($result['articles_tags']['exists']);
        $this->assertFalse($result['courses_schools']['exists']);
    }

    public function testScanReturnsEmptyArrayOnNoPivots(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $scanner = new PivotTableScanner($driver);

        $this->assertSame([], $scanner->scan([]));
    }

    public function testGetKnownPivotTableNamesReturnsNames(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $scanner = new PivotTableScanner($driver);

        $pivots = [
            $this->makePivot('articles_tags'),
            $this->makePivot('courses_schools', 'course_id', 'school_id'),
        ];

        $names = $scanner->getKnownPivotTableNames($pivots);

        $this->assertContains('articles_tags',   $names);
        $this->assertContains('courses_schools',  $names);
        $this->assertCount(2, $names);
    }

    public function testGetKnownPivotTableNamesReturnsEmptyOnNoPivots(): void
    {
        $driver = $this->createStub(DatabaseScannerDriverInterface::class);
        $scanner = new PivotTableScanner($driver);

        $this->assertSame([], $scanner->getKnownPivotTableNames([]));
    }
}

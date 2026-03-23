<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Attributes\ManyToMany;
use App\Kernel\Connector\Management\PivotTableManager;
use App\Kernel\Connector\Interfaces\ConnectorInterface;

class PivotTableManagerTest extends TestCase
{
    public function testGetTableNameFromPivotAttribute(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $manager = new PivotTableManager($connector);
        $attr = new ManyToMany(
            targetEntity: 'SchoolEntity',
            ownerColumn: 'course_id',
            targetColumn: 'school_id',
            pivotTable: 'courses_schools'
        );

        $result = $manager->getTableName('courses', 'schools', $attr);

        $this->assertEquals('courses_schools', $result);
    }

    public function testGetTableNameGeneratedAlphabetical(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $manager = new PivotTableManager($connector);
        $attr = new ManyToMany(
            targetEntity: 'SchoolEntity',
            ownerColumn: 'course_id',
            targetColumn: 'school_id',
        );

        $result = $manager->getTableName('courses', 'schools', $attr);

        $this->assertEquals('courses_schools', $result);
    }

    public function testGetTableNameGeneratedAlphabeticalReversed(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $manager = new PivotTableManager($connector);
        $attr = new ManyToMany(
            targetEntity: 'CourseEntity',
            ownerColumn: 'school_id',
            targetColumn: 'course_id',
        );

        $result = $manager->getTableName('schools', 'courses', $attr);

        $this->assertEquals('courses_schools', $result);
    }

    public function testGetTableNameSymmetric(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $manager = new PivotTableManager($connector);

        $attrFromCourse = new ManyToMany(
            targetEntity: 'SchoolEntity',
            ownerColumn: 'course_id',
            targetColumn: 'school_id',
        );
        $attrFromSchool = new ManyToMany(
            targetEntity: 'CourseEntity',
            ownerColumn: 'school_id',
            targetColumn: 'course_id',
        );

        $fromCourse = $manager->getTableName('courses', 'schools', $attrFromCourse);
        $fromSchool = $manager->getTableName('schools', 'courses', $attrFromSchool);

        $this->assertEquals($fromCourse, $fromSchool);
    }

    public function testLoadRelatedIdsReturnsIds(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn([
                ['course_id' => 1, 'school_id' => 10],
                ['course_id' => 1, 'school_id' => 20],
                ['course_id' => 1, 'school_id' => 30],
            ]);

        $manager = new PivotTableManager($connector);
        $result = $manager->loadRelatedIds('courses_schools', 'course_id', 1);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContains(10, $result);
        $this->assertContains(20, $result);
        $this->assertContains(30, $result);
    }

    public function testLoadRelatedIdsReturnsEmptyArray(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn([]);

        $manager = new PivotTableManager($connector);
        $result  = $manager->loadRelatedIds('courses_schools', 'course_id', 99);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testLoadRelatedIdsReturnsIntegers(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn([
                ['course_id' => 1, 'school_id' => '42'], // valeur string depuis PDO
            ]);
 
        $manager = new PivotTableManager($connector);
        $result  = $manager->loadRelatedIds('courses_schools', 'course_id', 1);
 
        $this->assertIsInt($result[0]);
        $this->assertEquals(42, $result[0]);
    }

    public function testLoadRelatedIdsUsesCorrectOwnerColumn(): void
    {
        $connector = $this->createMock(ConnectorInterface::class);
        $connector->expects($this->once())
            ->method('fetchQuery')
            ->with(
                'SELECT * FROM courses_schools WHERE course_id = :id',
                [':id' => 5]
            )
            ->willReturn([]);
 
        $manager = new PivotTableManager($connector);
        $manager->loadRelatedIds('courses_schools', 'course_id', 5);
    }

    public function testSyncDeletesExistingRowsFirst(): void
    {
        $connector = $this->createMock(ConnectorInterface::class);
        $connector->expects($this->once())
            ->method('executeQuery')
            ->with(
                'DELETE FROM courses_schools WHERE course_id = :id',
                ['id' => 1]
            );
 
        $manager = new PivotTableManager($connector);
        $manager->sync('courses_schools', 'course_id', 'school_id', 1, []);
    }

    public function testSyncInsertsNewRows(): void
    {
        $connector = $this->createMock(ConnectorInterface::class);
        $connector->expects($this->exactly(4))
            ->method('executeQuery')
            ->willReturnCallback(function (string $sql, array $params) {
                return true;
            });
 
        $manager = new PivotTableManager($connector);
        $manager->sync('courses_schools', 'course_id', 'school_id', 1, [10, 20, 30]);
    }

    public function testSyncWithEmptyIdsOnlyDeletes(): void
    {
        $connector = $this->createMock(ConnectorInterface::class);
        $connector->expects($this->once())
            ->method('executeQuery')
            ->with(
                'DELETE FROM courses_schools WHERE course_id = :id',
                ['id' => 1]
            );
 
        $manager = new PivotTableManager($connector);
        $manager->sync('courses_schools', 'course_id', 'school_id', 1, []);
    }

    public function testSyncInsertUsesCorrectParams(): void
    {
        $insertCalls = [];
        $connector   = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')
            ->willReturnCallback(function (string $sql, array $params) use (&$insertCalls) {
                if (str_starts_with($sql, 'INSERT')) {
                    $insertCalls[] = $params;
                }
                return true;
            });
 
        $manager = new PivotTableManager($connector);
        $manager->sync('courses_schools', 'course_id', 'school_id', 1, [10, 20]);
 
        $this->assertCount(2, $insertCalls);
        $this->assertEquals([':owner' => 1, ':target' => 10], $insertCalls[0]);
        $this->assertEquals([':owner' => 1, ':target' => 20], $insertCalls[1]);
    }

    public function testSyncReplacesPreviousRelations(): void
    {
        $deletedFor = [];
        $insertedIds = [];
 
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')
            ->willReturnCallback(
                function (string $sql, array $params) use (&$deletedFor, &$insertedIds) {
                    if (str_starts_with($sql, 'DELETE')) {
                        $deletedFor[] = $params['id'];
                    }
                    if (str_starts_with($sql, 'INSERT')) {
                        $insertedIds[] = $params[':target'];
                    }
                    return true;
                }
            );
 
        $manager = new PivotTableManager($connector);
        $manager->sync('courses_schools', 'course_id', 'school_id', 1, [10, 20]);
        $manager->sync('courses_schools', 'course_id', 'school_id', 1, [30]);
 
        $this->assertCount(2, $deletedFor);   
        $this->assertCount(3, $insertedIds);  
        $this->assertContains(30, $insertedIds);
    }
}

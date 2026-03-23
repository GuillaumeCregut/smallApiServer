<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Datas\LazyBag;
use App\Kernel\Connector\Attributes\ManyToOne;
use App\Kernel\Connector\Utils\EntityAnalyzer;
use App\Kernel\Connector\Attributes\ManyToMany;
use App\Kernel\Connector\Interfaces\EntityInterface;
use App\Kernel\Connector\Utils\SchemaSyncOrchestrator;
use App\Kernel\Connector\Utils\EntitySchemaTransformer;
use App\Kernel\Connector\Utils\EntityManyToManyTranformer;
use App\Kernel\Connector\Utils\Scanner\AbstractScannerDriver;
use App\Kernel\Connector\Utils\Migration\MysqlMigrationGenerator;

class MigrationIntegrationTest extends TestCase
{
    private MysqlMigrationGenerator $generator;
    protected function setUp(): void
    {
        $this->generator = new MysqlMigrationGenerator();
    }

    private function analyzeAndTransform(array $entityClasses): array
    {
        $entitySchema = [];
        $manyToManyRaw = [];

        foreach ($entityClasses as $fqcn) {
            $properties   = EntityAnalyzer::getStoredProperties($fqcn, true);
            $m2mRelations = EntityAnalyzer::getManyToManyRelations($fqcn, true);

            $tableName = $this->classToTableName($fqcn);

            $transformer = new EntitySchemaTransformer();
            $transformed = $transformer->transform($tableName, $properties);
            if (null !== $transformed) {
                $entitySchema[$tableName] = $transformed;
            }

            if (!empty($m2mRelations)) {
                $manyToManyRaw[$tableName] = $m2mRelations;
            }
        }

        $pivotTransformer = new EntityManyToManyTranformer();
        $expectedPivots   = $pivotTransformer->transform($manyToManyRaw);

        return [
            'entitySchema'   => $entitySchema,
            'expectedPivots' => $expectedPivots,
        ];
    }

    private function classToTableName(string $fqcn): string
    {
        $short = (new \ReflectionClass($fqcn))->getShortName();
        $name  = preg_replace('/Entity$/', '', $short);
        return \App\Kernel\Connector\Utils\Helper::propertyToColumn($name) . 's';
    }

    private function makeDriver(
        ?array $dbSchema     = null,
        array  $pivotTables  = [],
        bool   $pivotsExist  = false,
        array  $pivotColumns = []
    ): AbstractScannerDriver {
        $driver = $this->createStub(AbstractScannerDriver::class);

        $allTables = array_merge(
            $dbSchema ? array_keys($dbSchema) : [],
            $pivotTables
        );

        $driver->method('getTables')->willReturn($allTables);

        $driver->method('scan')->willReturn(
            $dbSchema ? array_map(fn($t) => [
                'columns'      => $t['columns'],
                'primary_keys' => $t['primary_keys'] ?? [],
                'indexes'      => $t['indexes']      ?? [],
            ], $dbSchema) : []
        );

        $driver->method('tableExists')
            ->willReturnCallback(fn($table) => in_array($table, $pivotTables));

        $driver->method('getColumns')
            ->willReturnCallback(function (string $table) use ($pivotTables, $pivotsExist, $pivotColumns) {
                if (in_array($table, $pivotTables) && $pivotsExist) {
                    return $pivotColumns[$table] ?? [];
                }
                return [];
            });

        $driver->method('getForeignKeys')
            ->willReturnCallback(function (string $table) use ($pivotTables, $pivotsExist, $pivotColumns) {
                if (in_array($table, $pivotTables) && $pivotsExist) {
                    $cols = $pivotColumns[$table] ?? [];
                    $fks  = [];
                    foreach ($cols as $col => $def) {
                        if (isset($def['fk'])) {
                            $fks[$col] = $def;
                        }
                    }
                    return $fks;
                }
                return [];
            });

        return $driver;
    }

    private function runPipeline(array $entityClasses, AbstractScannerDriver $driver): array
    {
        $data           = $this->analyzeAndTransform($entityClasses);
        $orchestrator   = new SchemaSyncOrchestrator($driver);
        $result         = $orchestrator->run($data['entitySchema'], $data['expectedPivots']);
        $entitySql      = $this->generator->generate($result['entities']);
        $pivotSql       = $this->generator->generatePivot($result['pivots']);

        return [
            'safe'        => array_merge($entitySql['safe'],        $pivotSql['safe']),
            'destructive' => array_merge($entitySql['destructive'],  $pivotSql['destructive']),
            'result'      => $result,
        ];
    }

    public function testSimpleEntityOnEmptyDatabaseGeneratesCreateTable(): void
    {
        $driver = $this->makeDriver(null);
        $output = $this->runPipeline([IntArticleEntity::class], $driver);

        $this->assertNotEmpty($output['safe']);
        $this->assertEmpty($output['destructive']);

        $createSql = implode("\n", $output['safe']);
        $this->assertStringContainsString('CREATE TABLE `int_articles`', $createSql);
        $this->assertStringContainsString('`id` INT NOT NULL AUTO_INCREMENT', $createSql);
        $this->assertStringContainsString('`title` VARCHAR(255) NOT NULL', $createSql);
        $this->assertStringContainsString('PRIMARY KEY (`id`)', $createSql);
        $this->assertStringContainsString('ENGINE=InnoDB', $createSql);
    }

    public function testSimpleEntityAlreadySyncedGeneratesNoSQL(): void
    {
        $dbSchema = [
            'int_articles' => [
                'columns' => [
                    'id'    => ['nullable' => false, 'type' => 'int'],
                    'title' => ['nullable' => false, 'type' => 'string'],
                ],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ]
        ];
        $driver = $this->makeDriver($dbSchema);
        $output = $this->runPipeline([IntArticleEntity::class], $driver);

        $this->assertEmpty($output['safe']);
        $this->assertEmpty($output['destructive']);
    }

    public function testSimpleEntityWithMissingColumnGeneratesAddColumn(): void
    {
        $dbSchema = [
            'int_articles' => [
                'columns'      => ['id' => ['nullable' => false, 'type' => 'int']],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ]
        ];
        $driver = $this->makeDriver($dbSchema);
        $output = $this->runPipeline([IntArticleEntity::class], $driver);

        $addSql = implode("\n", $output['safe']);
        $this->assertStringContainsString('ALTER TABLE `int_articles` ADD COLUMN `title`', $addSql);
    }

     public function testExtraTableInDatabaseGeneratesDropTable(): void
    {
        $dbSchema = [
            'int_articles' => [
                'columns'      => [
                    'id'    => ['nullable' => false, 'type' => 'int'],
                    'title' => ['nullable' => false, 'type' => 'string'],
                ],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ],
            'obsolete_table' => [
                'columns'      => ['id' => ['nullable' => false, 'type' => 'int']],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ],
        ];
        $driver = $this->makeDriver($dbSchema);
        $output = $this->runPipeline([IntArticleEntity::class], $driver);

        $this->assertContains('DROP TABLE `obsolete_table`;', $output['destructive']);
    }

    public function testColumnTypeChangeGeneratesAlterColumn(): void
    {
        $dbSchema = [
            'int_articles' => [
                'columns' => [
                    'id'    => ['nullable' => false, 'type' => 'int'],
                    'title' => ['nullable' => false, 'type' => 'int'], 
                ],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ]
        ];
        $driver = $this->makeDriver($dbSchema);
        $output = $this->runPipeline([IntArticleEntity::class], $driver);

        $safeSql = implode("\n", $output['safe']);
        $this->assertStringContainsString('MODIFY COLUMN `title`', $safeSql);
        $this->assertStringContainsString('VARCHAR(255)', $safeSql);
    }

    public function testManyToOneEntityOnEmptyDatabaseGeneratesCreateTablesAndConstraint(): void
    {
        $driver = $this->makeDriver(null);
        $output = $this->runPipeline([IntAuthorEntity::class, IntPostEntity::class], $driver);

        $safeSql = implode("\n", $output['safe']);
        $this->assertStringContainsString('CREATE TABLE `int_authors`', $safeSql);
        $this->assertStringContainsString('CREATE TABLE `int_posts`',   $safeSql);
        $this->assertStringContainsString('`author_id` INT NOT NULL',   $safeSql);
        $this->assertStringContainsString('FOREIGN KEY (`author_id`)',  $safeSql);
        $this->assertStringContainsString('REFERENCES `int_authors`',   $safeSql);
        $this->assertStringContainsString('ON DELETE CASCADE',          $safeSql);
    }

    public function testManyToOneAlreadySyncedGeneratesNoSQL(): void
    {
        $dbSchema = [
            'int_authors' => [
                'columns'      => ['id' => ['nullable' => false, 'type' => 'int'], 'name' => ['nullable' => false, 'type' => 'string']],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ],
            'int_posts' => [
                'columns' => [
                    'id'        => ['nullable' => false, 'type' => 'int'],
                    'title'     => ['nullable' => false, 'type' => 'string'],
                    'author_id' => ['nullable' => false, 'type' => 'int', 'fk' => 'int_authors', 'onDelete' => 'CASCADE', 'onUpdate' => 'RESTRICT'],
                ],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ],
        ];
        $driver = $this->makeDriver($dbSchema);
        $output = $this->runPipeline([IntAuthorEntity::class, IntPostEntity::class], $driver);

        $this->assertEmpty($output['safe']);
        $this->assertEmpty($output['destructive']);
    }

    public function testManyToOneMissingConstraintGeneratesAddConstraint(): void
    {
        $dbSchema = [
            'int_authors' => [
                'columns'      => ['id' => ['nullable' => false, 'type' => 'int'], 'name' => ['nullable' => false, 'type' => 'string']],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ],
            'int_posts' => [
                'columns' => [
                    'id'        => ['nullable' => false, 'type' => 'int'],
                    'title'     => ['nullable' => false, 'type' => 'string'],
                    'author_id' => ['nullable' => false, 'type' => 'int'], 
                ],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ],
        ];
        $driver = $this->makeDriver($dbSchema);
        $output = $this->runPipeline([IntAuthorEntity::class, IntPostEntity::class], $driver);

        $safeSql = implode("\n", $output['safe']);
        $this->assertStringContainsString('ADD CONSTRAINT', $safeSql);
        $this->assertStringContainsString('FOREIGN KEY (`author_id`)', $safeSql);
    }

    public function testManyToManyOnEmptyDatabaseGeneratesCreateTableAndPivot(): void
    {
        $driver = $this->makeDriver(null);
        $output = $this->runPipeline([IntCourseEntity::class, IntSchoolEntity::class], $driver);

        $safeSql = implode("\n", $output['safe']);
        $this->assertStringContainsString('CREATE TABLE `int_courses`',         $safeSql);
        $this->assertStringContainsString('CREATE TABLE `int_schools`',         $safeSql);
        $this->assertStringContainsString('CREATE TABLE `int_courses_int_schools`', $safeSql);
        $this->assertStringContainsString('`course_id` INT NOT NULL',            $safeSql);
        $this->assertStringContainsString('`school_id` INT NOT NULL',            $safeSql);
        $this->assertStringContainsString('PRIMARY KEY (`course_id`, `school_id`)', $safeSql);
    }

    public function testManyToManyAlreadySyncedGeneratesNoSQL(): void
    {
        $pivotTable = 'int_courses_int_schools';
        $pivotCols  = [
            $pivotTable => [
                'course_id' => ['nullable' => false, 'type' => 'int', 'fk' => 'int_courses'],
                'school_id' => ['nullable' => false, 'type' => 'int', 'fk' => 'int_schools'],
            ]
        ];
        $dbSchema = [
            'int_courses' => [
                'columns'      => ['id' => ['nullable' => false, 'type' => 'int'], 'name' => ['nullable' => false, 'type' => 'string']],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ],
            'int_schools' => [
                'columns'      => ['id' => ['nullable' => false, 'type' => 'int'], 'name' => ['nullable' => false, 'type' => 'string']],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ],
        ];
        $driver = $this->makeDriver($dbSchema, [$pivotTable], true, $pivotCols);
        $output = $this->runPipeline([IntCourseEntity::class, IntSchoolEntity::class], $driver);

        $this->assertEmpty($output['safe']);
        $this->assertEmpty($output['destructive']);
    }

    public function testManyToManyPivotMissingGeneratesCreatePivot(): void
    {
        $dbSchema = [
            'int_courses' => [
                'columns'      => ['id' => ['nullable' => false, 'type' => 'int'], 'name' => ['nullable' => false, 'type' => 'string']],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ],
            'int_schools' => [
                'columns'      => ['id' => ['nullable' => false, 'type' => 'int'], 'name' => ['nullable' => false, 'type' => 'string']],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ],
        ];
        
        $driver = $this->makeDriver($dbSchema, [], false);
        $output = $this->runPipeline([IntCourseEntity::class, IntSchoolEntity::class], $driver);

        $safeSql = implode("\n", $output['safe']);
        $this->assertStringContainsString('CREATE TABLE `int_courses_int_schools`', $safeSql);
    }

     public function testManyToManyPivotColumnsMissingGeneratesTodoComment(): void
    {
        $pivotTable = 'int_courses_int_schools';
        
        $pivotCols = [
            $pivotTable => [
                'course_id' => ['nullable' => false, 'type' => 'int'],
               
            ]
        ];
        $dbSchema = [
            'int_courses' => [
                'columns'      => ['id' => ['nullable' => false, 'type' => 'int'], 'name' => ['nullable' => false, 'type' => 'string']],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ],
            'int_schools' => [
                'columns'      => ['id' => ['nullable' => false, 'type' => 'int'], 'name' => ['nullable' => false, 'type' => 'string']],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ],
        ];
        $driver = $this->makeDriver($dbSchema, [$pivotTable], true, $pivotCols);
        $output = $this->runPipeline([IntCourseEntity::class, IntSchoolEntity::class], $driver);

        $safeSql = implode("\n", $output['safe']);
        $this->assertStringContainsString('TODO', $safeSql);
        $this->assertStringContainsString('int_courses_int_schools', $safeSql);

       
        $this->assertStringContainsString('DROP TABLE `int_courses_int_schools`', implode("\n", $output['destructive']));
    }

    public function testRemovedEntityGeneratesDropTable(): void
    {
        $dbSchema = [
            'int_articles' => [
                'columns'      => ['id' => ['nullable' => false, 'type' => 'int'], 'title' => ['nullable' => false, 'type' => 'string']],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ],
            'int_removed' => [
                'columns'      => ['id' => ['nullable' => false, 'type' => 'int']],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ],
        ];
        $driver = $this->makeDriver($dbSchema);
        $output = $this->runPipeline([IntArticleEntity::class], $driver);

        $this->assertContains('DROP TABLE `int_removed`;', $output['destructive']);
    }

    public function testOrphanPivotTableGeneratesDropPivot(): void
    {
        $orphanPivot = 'old_pivot_table';
        $orphanCols  = [
            $orphanPivot => [
                'entity_a_id' => ['nullable' => false, 'type' => 'int', 'fk' => 'entity_as'],
                'entity_b_id' => ['nullable' => false, 'type' => 'int', 'fk' => 'entity_bs'],
            ]
        ];
        $dbSchema = [
            'int_articles' => [
                'columns'      => ['id' => ['nullable' => false, 'type' => 'int'], 'title' => ['nullable' => false, 'type' => 'string']],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ],
        ];

        $driver = $this->createStub(AbstractScannerDriver::class);
        $driver->method('getTables')->willReturn(['int_articles', $orphanPivot]);
        $driver->method('scan')->willReturn([
            'int_articles' => [
                'columns'      => ['id' => ['nullable' => false, 'type' => 'int'], 'title' => ['nullable' => false, 'type' => 'string']],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ]
        ]);
        $driver->method('tableExists')->willReturn(false);
        $driver->method('getColumns')
            ->willReturnCallback(fn($t) => $orphanCols[$t] ?? []);
        $driver->method('getForeignKeys')
            ->willReturnCallback(fn($t) => array_filter(
                $orphanCols[$t] ?? [],
                fn($col) => isset($col['fk'])
            ));

        $output = $this->runPipeline([IntArticleEntity::class], $driver);

        $this->assertContains('DROP TABLE `old_pivot_table`;', $output['destructive']);
    }

    public function testMultipleEntitiesPartialSyncGeneratesCorrectDiff(): void
    {
        $dbSchema = [
            'int_articles' => [
                'columns'      => ['id' => ['nullable' => false, 'type' => 'int'], 'title' => ['nullable' => false, 'type' => 'string']],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ],
        ];
        $driver = $this->makeDriver($dbSchema);
        $output = $this->runPipeline([IntArticleEntity::class, IntAuthorEntity::class], $driver);

        $safeSql = implode("\n", $output['safe']);
        $this->assertStringContainsString('CREATE TABLE `int_authors`', $safeSql);
        $this->assertStringNotContainsString('CREATE TABLE `int_articles`', $safeSql);
        $this->assertEmpty($output['destructive']);
    }
}

final class IntArticleEntity implements EntityInterface
{
    private ?int $id = null;
    private string $title = '';

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): static { $this->id = $id; return $this; }
    public static function getRepository(): ?string { return null; }
}

final class IntAuthorEntity implements EntityInterface
{
    private ?int $id = null;
    private string $name = '';

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): static { $this->id = $id; return $this; }
    public static function getRepository(): ?string { return null; }
}

final class IntPostEntity implements EntityInterface
{
    private ?int $id = null;
    private string $title = '';

    #[ManyToOne(
        targetEntity: IntAuthorEntity::class,
        inversedBy: 'posts',
        onDelete: 'CASCADE',
        onUpdate: 'RESTRICT'
    )]
    private ?IntAuthorEntity $author = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): static { $this->id = $id; return $this; }
    public static function getRepository(): ?string { return null; }
    public function getAuthor(): ?IntAuthorEntity { return $this->author; }
    public function setAuthor(?IntAuthorEntity $author): self { $this->author = $author; return $this; }
}

final class IntSchoolEntity implements EntityInterface
{
    private ?int $id = null;
    private string $name = '';

    #[ManyToMany(
        targetEntity: IntCourseEntity::class,
        ownerColumn: 'school_id',
        targetColumn: 'course_id',
        mappedBy: 'schools',
        pivotTable: 'int_courses_int_schools'
    )]
    private ?LazyBag $courses = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): static { $this->id = $id; return $this; }
    public static function getRepository(): ?string { return null; }
    public function getCourses(): ?LazyBag { return $this->courses; }
    public function setCourses(?LazyBag $courses): self { $this->courses = $courses; return $this; }
}

final class IntCourseEntity implements EntityInterface
{
    private ?int $id = null;
    private string $name = '';

    #[ManyToMany(
        targetEntity: IntSchoolEntity::class,
        ownerColumn: 'course_id',
        targetColumn: 'school_id',
        inversedBy: 'courses',
        pivotTable: 'int_courses_int_schools'
    )]
    private ?LazyBag $schools = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): static { $this->id = $id; return $this; }
    public static function getRepository(): ?string { return null; }
    public function getSchools(): ?LazyBag { return $this->schools; }
    public function setSchools(?LazyBag $schools): self { $this->schools = $schools; return $this; }
}
<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Utils\Migration\SqliteMigrationGenerator;

class SqliteMigrationGeneratorTest extends TestCase
{
    private SqliteMigrationGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new SqliteMigrationGenerator();
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
    // Empty diff
    // -------------------------------------------------------------------------

    public function testEmptyDiffProducesNoSQL(): void
    {
        $sql = $this->generator->generate($this->emptyDiff());

        $this->assertEmpty($sql['safe']);
        $this->assertEmpty($sql['destructive']);
    }

    // -------------------------------------------------------------------------
    // CREATE TABLE
    // -------------------------------------------------------------------------

    public function testGeneratesCreateTable(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'tables_to_create' => [
                'users' => [
                    'id'    => ['nullable' => false, 'type' => 'int'],
                    'email' => ['nullable' => false, 'type' => 'string'],
                ]
            ]
        ]);

        $sql = $this->generator->generate($diff);

        $this->assertCount(1, $sql['safe']);
        $this->assertStringContainsString('CREATE TABLE "users"', $sql['safe'][0]);
        // SQLite uses INTEGER AUTOINCREMENT for auto-increment
        $this->assertStringContainsString('"id" INTEGER NOT NULL AUTOINCREMENT', $sql['safe'][0]);
        $this->assertStringContainsString('"email" TEXT NOT NULL', $sql['safe'][0]);
        $this->assertStringContainsString('PRIMARY KEY ("id")', $sql['safe'][0]);
    }

    public function testCreateTableWithFKColumnGeneratesSeparateConstraintWarning(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'tables_to_create' => [
                'posts' => [
                    'id'        => ['nullable' => false, 'type' => 'int'],
                    'author_id' => ['nullable' => false, 'type' => 'int', 'fk' => 'authors', 'onDelete' => 'CASCADE', 'onUpdate' => 'RESTRICT'],
                ]
            ]
        ]);

        $sql = $this->generator->generate($diff);

        // Two safe statements: CREATE TABLE + warning comment
        $this->assertCount(2, $sql['safe']);

        $this->assertStringContainsString('"author_id" INTEGER NOT NULL', $sql['safe'][0]);
        $this->assertStringNotContainsString('CONSTRAINT', $sql['safe'][0]);

        // Second statement is a warning comment, not real SQL
        $this->assertStringContainsString('-- WARNING', $sql['safe'][1]);
        $this->assertStringContainsString('SQLite does not support ADD CONSTRAINT', $sql['safe'][1]);
        $this->assertStringContainsString('"author_id"', $sql['safe'][1]);
        $this->assertStringContainsString('"posts"', $sql['safe'][1]);
    }

    public function testCreateTableWithNullableColumn(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'tables_to_create' => [
                'users' => [
                    'id'  => ['nullable' => false, 'type' => 'int'],
                    'age' => ['nullable' => true,  'type' => 'int'],
                ]
            ]
        ]);

        $sql = $this->generator->generate($diff);

        $this->assertStringContainsString('"age" INTEGER NULL', $sql['safe'][0]);
    }

    public function testCreateTableWithBoolColumn(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'tables_to_create' => [
                'users' => [
                    'id'       => ['nullable' => false, 'type' => 'int'],
                    'is_admin' => ['nullable' => false, 'type' => 'bool'],
                ]
            ]
        ]);

        $sql = $this->generator->generate($diff);

        // SQLite has no native bool — stored as INTEGER
        $this->assertStringContainsString('"is_admin" INTEGER NOT NULL', $sql['safe'][0]);
    }

    public function testCreateTableWithJsonColumn(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'tables_to_create' => [
                'users' => [
                    'id'   => ['nullable' => false, 'type' => 'int'],
                    'tags' => ['nullable' => true,  'type' => 'json'],
                ]
            ]
        ]);

        $sql = $this->generator->generate($diff);

        // SQLite has no native JSON — stored as TEXT
        $this->assertStringContainsString('"tags" TEXT NULL', $sql['safe'][0]);
    }

    public function testCreateTableWithDatetimeColumn(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'tables_to_create' => [
                'events' => [
                    'id'         => ['nullable' => false, 'type' => 'int'],
                    'created_at' => ['nullable' => false, 'type' => 'datetime'],
                ]
            ]
        ]);

        $sql = $this->generator->generate($diff);

        // SQLite has no native datetime — stored as TEXT
        $this->assertStringContainsString('"created_at" TEXT NOT NULL', $sql['safe'][0]);
    }

    // -------------------------------------------------------------------------
    // DROP TABLE
    // -------------------------------------------------------------------------

    public function testDropTableIsDestructive(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'tables_to_drop' => ['obsolete_table']
        ]);

        $sql = $this->generator->generate($diff);

        $this->assertEmpty($sql['safe']);
        $this->assertCount(1, $sql['destructive']);
        $this->assertEquals('DROP TABLE "obsolete_table";', $sql['destructive'][0]);
    }

    // -------------------------------------------------------------------------
    // ADD COLUMN
    // -------------------------------------------------------------------------

    public function testGeneratesAddColumn(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'columns_to_add' => [
                'users' => [
                    'phone' => ['nullable' => true, 'type' => 'string'],
                ]
            ]
        ]);

        $sql = $this->generator->generate($diff);

        $this->assertCount(1, $sql['safe']);
        $this->assertStringContainsString('ALTER TABLE "users" ADD COLUMN', $sql['safe'][0]);
        $this->assertStringContainsString('"phone" TEXT NULL', $sql['safe'][0]);
    }

    public function testGeneratesAddFKColumnWithConstraintWarning(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'columns_to_add' => [
                'posts' => [
                    'author_id' => ['nullable' => false, 'type' => 'int', 'fk' => 'authors', 'onDelete' => 'CASCADE', 'onUpdate' => 'RESTRICT'],
                ]
            ]
        ]);

        $sql = $this->generator->generate($diff);

        // Two safe statements: ADD COLUMN + warning comment
        $this->assertCount(2, $sql['safe']);

        $this->assertStringContainsString('ALTER TABLE "posts" ADD COLUMN "author_id" INTEGER NOT NULL', $sql['safe'][0]);

        // Constraint generates a warning, not real SQL
        $this->assertStringContainsString('-- WARNING', $sql['safe'][1]);
        $this->assertStringContainsString('SQLite does not support ADD CONSTRAINT', $sql['safe'][1]);
        $this->assertStringContainsString('"author_id"', $sql['safe'][1]);
        $this->assertStringContainsString('"posts"', $sql['safe'][1]);
    }

    // -------------------------------------------------------------------------
    // DROP COLUMN
    // -------------------------------------------------------------------------

    public function testDropColumnIsDestructive(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'columns_to_drop' => ['users' => ['obsolete']]
        ]);

        $sql = $this->generator->generate($diff);

        $this->assertEmpty($sql['safe']);
        $this->assertCount(1, $sql['destructive']);
        $this->assertEquals('ALTER TABLE "users" DROP COLUMN "obsolete";', $sql['destructive'][0]);
    }

    // -------------------------------------------------------------------------
    // ALTER COLUMN
    // -------------------------------------------------------------------------

    public function testGeneratesAlterColumnWarning(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'columns_to_alter' => [
                'users' => [
                    'age' => [
                        'type'     => ['from' => 'int', 'to' => 'float'],
                        'nullable' => ['from' => false, 'to' => true],
                    ]
                ]
            ]
        ]);

        $sql = $this->generator->generate($diff);

        $this->assertCount(1, $sql['safe']);
        // SQLite cannot alter columns — generates a warning comment
        $this->assertStringContainsString('-- WARNING', $sql['safe'][0]);
        $this->assertStringContainsString('SQLite does not support ALTER COLUMN', $sql['safe'][0]);
        $this->assertStringContainsString('"age"', $sql['safe'][0]);
        $this->assertStringContainsString('"users"', $sql['safe'][0]);
    }

    public function testAlterColumnWarningContainsChangeDescription(): void
    {
        $changes = [
            'type'     => ['from' => 'int', 'to' => 'float'],
            'nullable' => ['from' => false, 'to' => true],
        ];

        $diff = array_merge($this->emptyDiff(), [
            'columns_to_alter' => [
                'users' => ['age' => $changes]
            ]
        ]);

        $sql = $this->generator->generate($diff);

        // The warning comment should include the JSON-encoded change details
        $this->assertStringContainsString(json_encode($changes, JSON_PRETTY_PRINT), $sql['safe'][0]);
    }

    // -------------------------------------------------------------------------
    // ADD CONSTRAINT
    // -------------------------------------------------------------------------

    public function testAddConstraintGeneratesWarning(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'constraints_to_add' => [
                'posts' => [
                    'author_id' => ['fk' => 'authors', 'onDelete' => 'CASCADE', 'onUpdate' => 'RESTRICT'],
                ]
            ]
        ]);

        $sql = $this->generator->generate($diff);

        $this->assertCount(1, $sql['safe']);
        // SQLite cannot add constraints — generates a warning comment
        $this->assertStringContainsString('-- WARNING', $sql['safe'][0]);
        $this->assertStringContainsString('SQLite does not support ADD CONSTRAINT', $sql['safe'][0]);
        $this->assertStringContainsString('"author_id"', $sql['safe'][0]);
        $this->assertStringContainsString('"posts"', $sql['safe'][0]);
    }

    public function testAddConstraintWarningContainsConstraintDefinition(): void
    {
        $constraintDef = ['fk' => 'authors', 'onDelete' => 'CASCADE', 'onUpdate' => 'RESTRICT'];

        $diff = array_merge($this->emptyDiff(), [
            'constraints_to_add' => [
                'posts' => ['author_id' => $constraintDef]
            ]
        ]);

        $sql = $this->generator->generate($diff);

        $this->assertStringContainsString(json_encode($constraintDef, JSON_PRETTY_PRINT), $sql['safe'][0]);
    }

    public function testConstraintWarningIsInSafeNotDestructive(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'constraints_to_add' => [
                'posts' => [
                    'author_id' => ['fk' => 'authors', 'onDelete' => 'CASCADE', 'onUpdate' => 'RESTRICT'],
                ]
            ]
        ]);

        $sql = $this->generator->generate($diff);

        $this->assertNotEmpty($sql['safe']);
        $this->assertEmpty($sql['destructive']);
    }

    // -------------------------------------------------------------------------
    // Mixed diff
    // -------------------------------------------------------------------------

    public function testMixedDiffGeneratesAllStatements(): void
    {
        $diff = [
            'tables_to_create'   => [
                'new_table' => ['id' => ['nullable' => false, 'type' => 'int']]
            ],
            'tables_to_drop'     => ['old_table'],
            'columns_to_add'     => [
                'users' => ['phone' => ['nullable' => true, 'type' => 'string']]
            ],
            'columns_to_drop'    => ['users' => ['obsolete']],
            'columns_to_alter'   => [
                'users' => ['age' => ['type' => ['from' => 'int', 'to' => 'float']]]
            ],
            'constraints_to_add' => [
                'posts' => ['author_id' => ['fk' => 'authors', 'onDelete' => 'CASCADE', 'onUpdate' => 'RESTRICT']]
            ],
        ];

        $sql = $this->generator->generate($diff);

        $this->assertCount(4, $sql['safe']);       // CREATE TABLE + ADD COLUMN + ALTER COLUMN (warning) + ADD CONSTRAINT (warning)
        $this->assertCount(2, $sql['destructive']); // DROP TABLE + DROP COLUMN
    }

    private function emptyPivotDiff(): array
    {
        return [
            'pivot_tables_to_create' => [],
            'pivot_tables_to_drop'   => [],
            'pivot_tables_to_fix'    => [],
        ];
    }

    private function makePivot(
        string $table       = 'articles_tags',
        string $ownerCol    = 'article_id',
        string $targetCol   = 'tag_id',
        string $ownerTable  = 'articles',
        string $targetTable = 'tags'
    ): array {
        return [
            'pivotTable'  => $table,
            'ownerTable'  => $ownerTable,
            'targetTable' => $targetTable,
            'ownerCol'    => $ownerCol,
            'targetCol'   => $targetCol,
        ];
    }

    public function testEmptyPivotDiffProducesNoSQL(): void
    {
        $sql = $this->generator->generatePivot($this->emptyPivotDiff());

        $this->assertEmpty($sql['safe']);
        $this->assertEmpty($sql['destructive']);
    }

    public function testGeneratesCreatePivotTable(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_create' => [$this->makePivot()]
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertCount(1, $sql['safe']);
        $this->assertEmpty($sql['destructive']);
        $this->assertStringContainsString('CREATE TABLE "articles_tags"', $sql['safe'][0]);
    }

    public function testCreatePivotTableContainsOwnerColumn(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_create' => [$this->makePivot()]
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertStringContainsString('"article_id" INTEGER NOT NULL', $sql['safe'][0]);
    }

    public function testCreatePivotTableContainsTargetColumn(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_create' => [$this->makePivot()]
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertStringContainsString('"tag_id" INTEGER NOT NULL', $sql['safe'][0]);
    }

    public function testCreatePivotTableContainsCompositePrimaryKey(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_create' => [$this->makePivot()]
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertStringContainsString('PRIMARY KEY ("article_id", "tag_id")', $sql['safe'][0]);
    }

    public function testCreatePivotTableContainsOwnerForeignKey(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_create' => [$this->makePivot()]
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertStringContainsString(
            'FOREIGN KEY ("article_id") REFERENCES "articles" ("id") ON DELETE CASCADE',
            $sql['safe'][0]
        );
    }

    public function testCreatePivotTableContainsTargetForeignKey(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_create' => [$this->makePivot()]
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertStringContainsString(
            'FOREIGN KEY ("tag_id") REFERENCES "tags" ("id") ON DELETE CASCADE',
            $sql['safe'][0]
        );
    }

    public function testCreatePivotTableDoesNotContainMysqlOptions(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_create' => [$this->makePivot()]
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertStringNotContainsString('ENGINE=InnoDB', $sql['safe'][0]);
        $this->assertStringNotContainsString('CHARSET', $sql['safe'][0]);
        $this->assertStringNotContainsString('COLLATE', $sql['safe'][0]);
    }

    public function testGeneratesMultipleCreatePivotTables(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_create' => [
                $this->makePivot('articles_tags',   'article_id', 'tag_id'),
                $this->makePivot('courses_schools', 'course_id',  'school_id', 'courses', 'schools'),
            ]
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertCount(2, $sql['safe']);
        $this->assertStringContainsString('"articles_tags"',   $sql['safe'][0]);
        $this->assertStringContainsString('"courses_schools"', $sql['safe'][1]);
    }

    public function testDropPivotTableIsDestructive(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_drop' => ['articles_tags']
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertEmpty($sql['safe']);
        $this->assertCount(1, $sql['destructive']);
        $this->assertEquals('DROP TABLE "articles_tags";', $sql['destructive'][0]);
    }

    public function testDropMultiplePivotTablesAreDestructive(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_drop' => ['articles_tags', 'courses_schools']
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertCount(2, $sql['destructive']);
        $this->assertStringContainsString('"articles_tags"',   $sql['destructive'][0]);
        $this->assertStringContainsString('"courses_schools"', $sql['destructive'][1]);
    }

    public function testFixPivotTableGeneratesDropAndTodo(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_fix' => [
                'articles_tags' => [
                    'missing_columns' => ['tag_id'],
                    'extra_columns'   => [],
                ]
            ]
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertCount(1, $sql['destructive']);
        $this->assertStringContainsString('DROP TABLE "articles_tags"', $sql['destructive'][0]);

        $this->assertCount(1, $sql['safe']);
        $this->assertStringContainsString('TODO', $sql['safe'][0]);
        $this->assertStringContainsString('articles_tags', $sql['safe'][0]);
        $this->assertStringContainsString('tag_id', $sql['safe'][0]);
    }


    public function testMixedPivotDiffGeneratesCorrectStatements(): void
    {
        $diff = [
            'pivot_tables_to_create' => [$this->makePivot('articles_tags', 'article_id', 'tag_id')],
            'pivot_tables_to_drop'   => ['old_pivot'],
            'pivot_tables_to_fix'    => [],
        ];

        $sql = $this->generator->generatePivot($diff);

        $this->assertCount(1, $sql['safe']);
        $this->assertCount(1, $sql['destructive']);
        $this->assertStringContainsString('CREATE TABLE "articles_tags"', $sql['safe'][0]);
        $this->assertStringContainsString('DROP TABLE "old_pivot"',       $sql['destructive'][0]);
    }
}

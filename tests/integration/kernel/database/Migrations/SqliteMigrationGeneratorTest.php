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
}

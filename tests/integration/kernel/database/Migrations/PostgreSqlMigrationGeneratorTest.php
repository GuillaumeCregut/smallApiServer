<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Utils\Migration\PostgreSqlMigrationGenerator;

class PostgreSqlMigrationGeneratorTest extends TestCase
{
    private PostgreSqlMigrationGenerator $generator;
 
    protected function setUp(): void
    {
        $this->generator = new PostgreSqlMigrationGenerator();
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
        // PostgreSQL uses SERIAL for auto-increment, not INT AUTO_INCREMENT
        $this->assertStringContainsString('"id" SERIAL NOT NULL', $sql['safe'][0]);
        $this->assertStringContainsString('"email" VARCHAR(255) NOT NULL', $sql['safe'][0]);
        $this->assertStringContainsString('PRIMARY KEY ("id")', $sql['safe'][0]);
    }
 
    public function testCreateTableWithFKColumnGeneratesSeparateConstraint(): void
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
 
        // Two safe statements: CREATE TABLE + ADD CONSTRAINT
        $this->assertCount(2, $sql['safe']);
 
        // CREATE TABLE contains column but NO inline constraint
        $this->assertStringContainsString('"author_id" INTEGER NOT NULL', $sql['safe'][0]);
        $this->assertStringNotContainsString('CONSTRAINT', $sql['safe'][0]);
 
        // Second statement is ALTER TABLE ADD CONSTRAINT
        $this->assertStringContainsString('ALTER TABLE "posts"', $sql['safe'][1]);
        $this->assertStringContainsString('ADD CONSTRAINT "fk_posts_author_id"', $sql['safe'][1]);
        $this->assertStringContainsString('FOREIGN KEY ("author_id")', $sql['safe'][1]);
        $this->assertStringContainsString('REFERENCES "authors" ("id")', $sql['safe'][1]);
        $this->assertStringContainsString('ON DELETE CASCADE', $sql['safe'][1]);
        $this->assertStringContainsString('ON UPDATE RESTRICT', $sql['safe'][1]);
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
 
        // PostgreSQL uses BOOLEAN, not TINYINT(1)
        $this->assertStringContainsString('"is_admin" BOOLEAN NOT NULL', $sql['safe'][0]);
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
 
        // PostgreSQL uses JSONB, not JSON
        $this->assertStringContainsString('"tags" JSONB NULL', $sql['safe'][0]);
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
        $this->assertStringContainsString('"phone" VARCHAR(255) NULL', $sql['safe'][0]);
    }
 
    public function testGeneratesAddFKColumn(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'columns_to_add' => [
                'posts' => [
                    'author_id' => ['nullable' => false, 'type' => 'int', 'fk' => 'authors', 'onDelete' => 'CASCADE', 'onUpdate' => 'RESTRICT'],
                ]
            ]
        ]);
 
        $sql = $this->generator->generate($diff);
 
        // Two safe statements: ADD COLUMN + ADD CONSTRAINT
        $this->assertCount(2, $sql['safe']);
 
        $this->assertStringContainsString('ALTER TABLE "posts" ADD COLUMN "author_id" INTEGER NOT NULL', $sql['safe'][0]);
 
        $this->assertStringContainsString('ALTER TABLE "posts"', $sql['safe'][1]);
        $this->assertStringContainsString('ADD CONSTRAINT "fk_posts_author_id"', $sql['safe'][1]);
        $this->assertStringContainsString('FOREIGN KEY ("author_id")', $sql['safe'][1]);
        $this->assertStringContainsString('REFERENCES "authors" ("id")', $sql['safe'][1]);
        $this->assertStringContainsString('ON DELETE CASCADE', $sql['safe'][1]);
        $this->assertStringContainsString('ON UPDATE RESTRICT', $sql['safe'][1]);
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
 
    public function testGeneratesAlterColumnForTypeChange(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'columns_to_alter' => [
                'users' => [
                    'age' => [
                        'type' => ['from' => 'int', 'to' => 'float'],
                    ]
                ]
            ]
        ]);
 
        $sql = $this->generator->generate($diff);
 
        $this->assertCount(1, $sql['safe']);
        // PostgreSQL uses ALTER COLUMN ... TYPE, not MODIFY COLUMN
        $this->assertStringContainsString('ALTER TABLE "users" ALTER COLUMN "age" TYPE NUMERIC(10,2)', $sql['safe'][0]);
    }
 
    public function testGeneratesAlterColumnForNullableChange(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'columns_to_alter' => [
                'users' => [
                    'age' => [
                        'nullable' => ['from' => false, 'to' => true],
                    ]
                ]
            ]
        ]);
 
        $sql = $this->generator->generate($diff);
 
        $this->assertCount(1, $sql['safe']);
        $this->assertStringContainsString('ALTER TABLE "users" ALTER COLUMN "age" DROP NOT NULL', $sql['safe'][0]);
    }
 
    public function testGeneratesAlterColumnForNullableToNotNull(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'columns_to_alter' => [
                'users' => [
                    'age' => [
                        'nullable' => ['from' => true, 'to' => false],
                    ]
                ]
            ]
        ]);
 
        $sql = $this->generator->generate($diff);
 
        $this->assertStringContainsString('ALTER TABLE "users" ALTER COLUMN "age" SET NOT NULL', $sql['safe'][0]);
    }
 
    public function testGeneratesAlterColumnForTypeAndNullableChange(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'columns_to_alter' => [
                'users' => [
                    'age' => [
                        'type'     => ['from' => 'int',   'to' => 'float'],
                        'nullable' => ['from' => false,   'to' => true],
                    ]
                ]
            ]
        ]);
 
        $sql = $this->generator->generate($diff);
 
        $this->assertCount(1, $sql['safe']);
        // PostgreSQL emits two ALTER statements joined by newline when both change
        $this->assertStringContainsString('ALTER COLUMN "age" TYPE', $sql['safe'][0]);
        $this->assertStringContainsString('ALTER COLUMN "age" DROP NOT NULL', $sql['safe'][0]);
    }
 
    // -------------------------------------------------------------------------
    // ADD CONSTRAINT
    // -------------------------------------------------------------------------
 
    public function testGeneratesAddConstraint(): void
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
        $this->assertStringContainsString('ALTER TABLE "posts"', $sql['safe'][0]);
        $this->assertStringContainsString('ADD CONSTRAINT "fk_posts_author_id"', $sql['safe'][0]);
        $this->assertStringContainsString('FOREIGN KEY ("author_id")', $sql['safe'][0]);
        $this->assertStringContainsString('REFERENCES "authors" ("id")', $sql['safe'][0]);
        $this->assertStringContainsString('ON DELETE CASCADE', $sql['safe'][0]);
        $this->assertStringContainsString('ON UPDATE RESTRICT', $sql['safe'][0]);
    }
 
    public function testGeneratesAddConstraintWithRestrictDefault(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'constraints_to_add' => [
                'posts' => [
                    'author_id' => ['fk' => 'authors'],  // no onDelete/onUpdate
                ]
            ]
        ]);
 
        $sql = $this->generator->generate($diff);
 
        $this->assertStringContainsString('ON DELETE RESTRICT', $sql['safe'][0]);
        $this->assertStringContainsString('ON UPDATE RESTRICT', $sql['safe'][0]);
    }
 
    public function testConstraintsAreInSafeNotDestructive(): void
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
 
        $this->assertCount(4, $sql['safe']);       // CREATE TABLE + ADD COLUMN + ALTER COLUMN + ADD CONSTRAINT
        $this->assertCount(2, $sql['destructive']); // DROP TABLE + DROP COLUMN
    }
}
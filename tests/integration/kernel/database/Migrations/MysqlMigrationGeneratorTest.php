<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Utils\Migration\MysqlMigrationGenerator;

class MysqlMigrationGeneratorTest extends TestCase
{
    private MysqlMigrationGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new MysqlMigrationGenerator();
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
        $this->assertStringContainsString('CREATE TABLE `users`', $sql['safe'][0]);
        $this->assertStringContainsString('`id` INT NOT NULL AUTO_INCREMENT', $sql['safe'][0]);
        $this->assertStringContainsString('`email` VARCHAR(255) NOT NULL', $sql['safe'][0]);
        $this->assertStringContainsString('PRIMARY KEY (`id`)', $sql['safe'][0]);
        $this->assertStringContainsString('ENGINE=InnoDB', $sql['safe'][0]);
        $this->assertStringContainsString('DEFAULT CHARSET=utf8mb4', $sql['safe'][0]);
        $this->assertStringContainsString('COLLATE=utf8mb4_unicode_ci', $sql['safe'][0]);
    }

    public function testCreateTableEndsWithMysqlEngineOptions(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'tables_to_create' => [
                'users' => [
                    'id' => ['nullable' => false, 'type' => 'int'],
                ]
            ]
        ]);

        $sql = $this->generator->generate($diff);

        $this->assertStringEndsWith(
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',
            $sql['safe'][0]
        );
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
        $this->assertStringContainsString('`author_id` INT NOT NULL', $sql['safe'][0]);
        $this->assertStringNotContainsString('CONSTRAINT', $sql['safe'][0]);

        // Second statement is ALTER TABLE ADD CONSTRAINT
        $this->assertStringContainsString('ALTER TABLE `posts`', $sql['safe'][1]);
        $this->assertStringContainsString('ADD CONSTRAINT `fk_posts_author_id`', $sql['safe'][1]);
        $this->assertStringContainsString('FOREIGN KEY (`author_id`)', $sql['safe'][1]);
        $this->assertStringContainsString('REFERENCES `authors` (`id`)', $sql['safe'][1]);
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

        $this->assertStringContainsString('`age` INT NULL', $sql['safe'][0]);
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

        $this->assertStringContainsString('`is_admin` TINYINT(1) NOT NULL', $sql['safe'][0]);
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

        $this->assertStringContainsString('`tags` JSON NULL', $sql['safe'][0]);
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
        $this->assertEquals('DROP TABLE `obsolete_table`;', $sql['destructive'][0]);
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
        $this->assertStringContainsString('ALTER TABLE `users` ADD COLUMN', $sql['safe'][0]);
        $this->assertStringContainsString('`phone` VARCHAR(255) NULL', $sql['safe'][0]);
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

        $this->assertStringContainsString('ALTER TABLE `posts` ADD COLUMN `author_id` INT NOT NULL', $sql['safe'][0]);

        $this->assertStringContainsString('ALTER TABLE `posts`', $sql['safe'][1]);
        $this->assertStringContainsString('ADD CONSTRAINT `fk_posts_author_id`', $sql['safe'][1]);
        $this->assertStringContainsString('FOREIGN KEY (`author_id`)', $sql['safe'][1]);
        $this->assertStringContainsString('REFERENCES `authors` (`id`)', $sql['safe'][1]);
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
        $this->assertEquals('ALTER TABLE `users` DROP COLUMN `obsolete`;', $sql['destructive'][0]);
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
                        'type'     => ['from' => 'int',   'to' => 'float'],
                        'nullable' => ['from' => false,   'to' => true],
                    ]
                ]
            ]
        ]);

        $sql = $this->generator->generate($diff);

        $this->assertCount(1, $sql['safe']);
        $this->assertStringContainsString('ALTER TABLE `users` MODIFY COLUMN', $sql['safe'][0]);
        $this->assertStringContainsString('`age` DECIMAL(10,2) NULL', $sql['safe'][0]);
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
        $this->assertStringContainsString('ALTER TABLE `posts`', $sql['safe'][0]);
        $this->assertStringContainsString('ADD CONSTRAINT `fk_posts_author_id`', $sql['safe'][0]);
        $this->assertStringContainsString('FOREIGN KEY (`author_id`)', $sql['safe'][0]);
        $this->assertStringContainsString('REFERENCES `authors` (`id`)', $sql['safe'][0]);
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

    public function testGeneratesMultipleConstraints(): void
    {
        $diff = array_merge($this->emptyDiff(), [
            'constraints_to_add' => [
                'posts' => [
                    'author_id'   => ['fk' => 'authors',    'onDelete' => 'CASCADE',  'onUpdate' => 'RESTRICT'],
                    'category_id' => ['fk' => 'categories', 'onDelete' => 'RESTRICT', 'onUpdate' => 'CASCADE'],
                ]
            ]
        ]);

        $sql = $this->generator->generate($diff);

        $this->assertCount(2, $sql['safe']);
        $this->assertStringContainsString('REFERENCES `authors`',    $sql['safe'][0]);
        $this->assertStringContainsString('REFERENCES `categories`', $sql['safe'][1]);
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

        $this->assertCount(4, $sql['safe']);       // CREATE TABLE + ADD COLUMN + ALTER COLUMN + ADD CONSTRAINT (existing table)
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
        string $table     = 'articles_tags',
        string $ownerCol  = 'article_id',
        string $targetCol = 'tag_id',
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
        $this->assertStringContainsString('CREATE TABLE `articles_tags`', $sql['safe'][0]);
    }

    public function testCreatePivotTableContainsOwnerColumn(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_create' => [$this->makePivot()]
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertStringContainsString('`article_id` INT NOT NULL', $sql['safe'][0]);
    }

    public function testCreatePivotTableContainsTargetColumn(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_create' => [$this->makePivot()]
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertStringContainsString('`tag_id` INT NOT NULL', $sql['safe'][0]);
    }

    public function testCreatePivotTableContainsCompositePrimaryKey(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_create' => [$this->makePivot()]
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertStringContainsString('PRIMARY KEY (`article_id`, `tag_id`)', $sql['safe'][0]);
    }

    public function testCreatePivotTableContainsOwnerForeignKey(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_create' => [$this->makePivot()]
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertStringContainsString(
            'FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE',
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
            'FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE',
            $sql['safe'][0]
        );
    }

    public function testCreatePivotTableContainsMysqlEngineOptions(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_create' => [$this->makePivot()]
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertStringContainsString('ENGINE=InnoDB', $sql['safe'][0]);
        $this->assertStringContainsString('DEFAULT CHARSET=utf8mb4', $sql['safe'][0]);
        $this->assertStringContainsString('COLLATE=utf8mb4_unicode_ci', $sql['safe'][0]);
    }

    public function testGeneratesMultipleCreatePivotTables(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_create' => [
                $this->makePivot('articles_tags',    'article_id', 'tag_id'),
                $this->makePivot('courses_schools',  'course_id',  'school_id', 'courses', 'schools'),
            ]
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertCount(2, $sql['safe']);
        $this->assertStringContainsString('`articles_tags`',   $sql['safe'][0]);
        $this->assertStringContainsString('`courses_schools`', $sql['safe'][1]);
    }

    public function testDropPivotTableIsDestructive(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_drop' => ['articles_tags']
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertEmpty($sql['safe']);
        $this->assertCount(1, $sql['destructive']);
        $this->assertEquals('DROP TABLE `articles_tags`;', $sql['destructive'][0]);
    }

    public function testDropMultiplePivotTablesAreDestructive(): void
    {
        $diff = array_merge($this->emptyPivotDiff(), [
            'pivot_tables_to_drop' => ['articles_tags', 'courses_schools']
        ]);

        $sql = $this->generator->generatePivot($diff);

        $this->assertCount(2, $sql['destructive']);
        $this->assertStringContainsString('`articles_tags`',   $sql['destructive'][0]);
        $this->assertStringContainsString('`courses_schools`', $sql['destructive'][1]);
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
        $this->assertStringContainsString('DROP TABLE `articles_tags`', $sql['destructive'][0]);

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
        $this->assertStringContainsString('CREATE TABLE `articles_tags`', $sql['safe'][0]);
        $this->assertStringContainsString('DROP TABLE `old_pivot`',       $sql['destructive'][0]);
    }
}

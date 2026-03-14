<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Utils\Migration\MysqlMigrationGenerator;
use App\Kernel\Connector\Utils\Migration\SqliteMigrationGenerator;
use App\Kernel\Connector\Utils\Migration\PostgreSqlMigrationGenerator;

class MigrationGeneratorDriverTest extends TestCase
{
    public function testMySQLCreateTable(): void
    {
        $driver = new MysqlMigrationGenerator();
        $diff   = [
            'tables_to_create' => [
                'users' => [
                    'id'    => ['nullable' => false, 'type' => 'int',    'relation' => []],
                    'email' => ['nullable' => false, 'type' => 'string', 'relation' => []],
                ]
            ],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [],
        ];

        $sql = $driver->generate($diff);

        $this->assertCount(1, $sql['safe']);
        $this->assertEmpty($sql['destructive']);
        $this->assertStringContainsString('CREATE TABLE `users`', $sql['safe'][0]);
        $this->assertStringContainsString('`id` INT NOT NULL AUTO_INCREMENT', $sql['safe'][0]);
        $this->assertStringContainsString('`email` VARCHAR(255) NOT NULL', $sql['safe'][0]);
        $this->assertStringContainsString('PRIMARY KEY (`id`)', $sql['safe'][0]);
    }

    public function testMySQLDropTable(): void
    {
        $driver = new MysqlMigrationGenerator();
        $diff   = [
            'tables_to_create' => [],
            'tables_to_drop'   => ['obsolete_table'],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [],
        ];

        $sql = $driver->generate($diff);

        $this->assertEmpty($sql['safe']);
        $this->assertCount(1, $sql['destructive']);
        $this->assertEquals('DROP TABLE `obsolete_table`;', $sql['destructive'][0]);
    }

    public function testMySQLAddColumn(): void
    {
        $driver = new MysqlMigrationGenerator();
        $diff   = [
            'tables_to_create' => [],
            'tables_to_drop'   => [],
            'columns_to_add'   => [
                'users' => [
                    'phone' => ['nullable' => true, 'type' => 'string', 'relation' => []],
                ]
            ],
            'columns_to_drop'  => [],
            'columns_to_alter' => [],
        ];

        $sql = $driver->generate($diff);

        $this->assertCount(1, $sql['safe']);
        $this->assertStringContainsString('ALTER TABLE `users` ADD COLUMN', $sql['safe'][0]);
        $this->assertStringContainsString('`phone` VARCHAR(255) NULL', $sql['safe'][0]);
    }

    public function testMySQLDropColumn(): void
    {
        $driver = new MysqlMigrationGenerator();
        $diff   = [
            'tables_to_create' => [],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => ['users' => ['obsolete']],
            'columns_to_alter' => [],
        ];

        $sql = $driver->generate($diff);

        $this->assertEmpty($sql['safe']);
        $this->assertCount(1, $sql['destructive']);
        $this->assertEquals('ALTER TABLE `users` DROP COLUMN `obsolete`;', $sql['destructive'][0]);
    }

    public function testMySQLAlterColumn(): void
    {
        $driver = new MysqlMigrationGenerator();
        $diff   = [
            'tables_to_create' => [],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [
                'users' => [
                    'age' => [
                        'type'     => ['from' => 'int',   'to' => 'float'],
                        'nullable' => ['from' => false,   'to' => true],
                    ]
                ]
            ],
        ];

        $sql = $driver->generate($diff);

        $this->assertCount(1, $sql['safe']);
        $this->assertStringContainsString('ALTER TABLE `users` MODIFY COLUMN', $sql['safe'][0]);
        $this->assertStringContainsString('`age` DECIMAL(10,2) NULL', $sql['safe'][0]);
    }

    public function testMySQLRelationColumnIsGeneratedAsInt(): void
    {
        $driver = new MysqlMigrationGenerator();
        $diff   = [
            'tables_to_create' => [
                'posts' => [
                    'id'        => ['nullable' => false, 'type' => 'int',      'relation' => []],
                    'author_id' => ['nullable' => false, 'type' => 'relation', 'relation' => ['entity' => 'authors', 'key' => 'author_id']],
                ]
            ],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [],
        ];

        $sql = $driver->generate($diff);

        $this->assertStringContainsString('`author_id` INT NOT NULL', $sql['safe'][0]);
    }

     public function testMySQLJsonType(): void
    {
        $driver = new MysqlMigrationGenerator();
        $diff   = [
            'tables_to_create' => [
                'users' => [
                    'id'   => ['nullable' => false, 'type' => 'int',  'relation' => []],
                    'tags' => ['nullable' => true,  'type' => 'json', 'relation' => []],
                ]
            ],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [],
        ];

        $sql = $driver->generate($diff);

        $this->assertStringContainsString('`tags` JSON NULL', $sql['safe'][0]);
    }

     public function testMySQLBoolType(): void
    {
        $driver = new MysqlMigrationGenerator();
        $diff   = [
            'tables_to_create' => [
                'users' => [
                    'id'       => ['nullable' => false, 'type' => 'int',  'relation' => []],
                    'is_admin' => ['nullable' => false, 'type' => 'bool', 'relation' => []],
                ]
            ],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [],
        ];

        $sql = $driver->generate($diff);

        $this->assertStringContainsString('`is_admin` TINYINT(1) NOT NULL', $sql['safe'][0]);
    }

    public function testPostgreSQLCreateTable(): void
    {
        $driver = new PostgreSqlMigrationGenerator();
        $diff   = [
            'tables_to_create' => [
                'users' => [
                    'id'    => ['nullable' => false, 'type' => 'int',    'relation' => []],
                    'email' => ['nullable' => false, 'type' => 'string', 'relation' => []],
                ]
            ],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [],
        ];

        $sql = $driver->generate($diff);

        $this->assertCount(1, $sql['safe']);
        $this->assertStringContainsString('CREATE TABLE "users"', $sql['safe'][0]);
        $this->assertStringContainsString('"id" SERIAL NOT NULL', $sql['safe'][0]);
        $this->assertStringContainsString('"email" VARCHAR(255) NOT NULL', $sql['safe'][0]);
        $this->assertStringContainsString('PRIMARY KEY ("id")', $sql['safe'][0]);
    }

    public function testPostgreSQLDropTable(): void
    {
        $driver = new PostgreSqlMigrationGenerator();
        $diff   = [
            'tables_to_create' => [],
            'tables_to_drop'   => ['obsolete_table'],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [],
        ];

        $sql = $driver->generate($diff);

        $this->assertEmpty($sql['safe']);
        $this->assertEquals('DROP TABLE "obsolete_table";', $sql['destructive'][0]);
    }

    public function testPostgreSQLAlterColumnType(): void
    {
        $driver = new PostgreSqlMigrationGenerator();
        $diff   = [
            'tables_to_create' => [],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [
                'users' => [
                    'age' => ['type' => ['from' => 'int', 'to' => 'float']]
                ]
            ],
        ];

        $sql = $driver->generate($diff);

        $this->assertCount(1, $sql['safe']);
        $this->assertStringContainsString('ALTER TABLE "users" ALTER COLUMN "age" TYPE NUMERIC(10,2)', $sql['safe'][0]);
    }

    public function testPostgreSQLAlterColumnNullabilityDropNotNull(): void
    {
        $driver = new PostgreSqlMigrationGenerator();
        $diff   = [
            'tables_to_create' => [],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [
                'users' => [
                    'email' => ['nullable' => ['from' => false, 'to' => true]]
                ]
            ],
        ];

        $sql = $driver->generate($diff);

        $this->assertStringContainsString('ALTER TABLE "users" ALTER COLUMN "email" DROP NOT NULL', $sql['safe'][0]);
    }

    public function testPostgreSQLAlterColumnNullabilitySetNotNull(): void
    {
        $driver = new PostgreSqlMigrationGenerator();
        $diff   = [
            'tables_to_create' => [],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [
                'users' => [
                    'email' => ['nullable' => ['from' => true, 'to' => false]]
                ]
            ],
        ];

        $sql = $driver->generate($diff);

        $this->assertStringContainsString('ALTER TABLE "users" ALTER COLUMN "email" SET NOT NULL', $sql['safe'][0]);
    }

    public function testPostgreSQLJsonbType(): void
    {
        $driver = new PostgreSqlMigrationGenerator();
        $diff   = [
            'tables_to_create' => [
                'users' => [
                    'id'   => ['nullable' => false, 'type' => 'int',  'relation' => []],
                    'tags' => ['nullable' => true,  'type' => 'json', 'relation' => []],
                ]
            ],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [],
        ];

        $sql = $driver->generate($diff);

        $this->assertStringContainsString('"tags" JSONB NULL', $sql['safe'][0]);
    }

    public function testPostgreSQLBoolType(): void
    {
        $driver = new PostgreSqlMigrationGenerator();
        $diff   = [
            'tables_to_create' => [
                'users' => [
                    'id'       => ['nullable' => false, 'type' => 'int',  'relation' => []],
                    'is_admin' => ['nullable' => false, 'type' => 'bool', 'relation' => []],
                ]
            ],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [],
        ];

        $sql = $driver->generate($diff);

        $this->assertStringContainsString('"is_admin" BOOLEAN NOT NULL', $sql['safe'][0]);
    }

    public function testSQLiteCreateTable(): void
    {
        $driver = new SqliteMigrationGenerator();
        $diff   = [
            'tables_to_create' => [
                'users' => [
                    'id'    => ['nullable' => false, 'type' => 'int',    'relation' => []],
                    'email' => ['nullable' => false, 'type' => 'string', 'relation' => []],
                ]
            ],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [],
        ];

        $sql = $driver->generate($diff);

        $this->assertCount(1, $sql['safe']);
        $this->assertStringContainsString('CREATE TABLE "users"', $sql['safe'][0]);
        $this->assertStringContainsString('"id" INTEGER NOT NULL AUTOINCREMENT', $sql['safe'][0]);
        $this->assertStringContainsString('"email" TEXT NOT NULL', $sql['safe'][0]);
    }

     public function testSQLiteAlterColumnGeneratesWarningComment(): void
    {
        $driver = new SqliteMigrationGenerator();
        $diff   = [
            'tables_to_create' => [],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [
                'users' => [
                    'age' => ['type' => ['from' => 'int', 'to' => 'float']]
                ]
            ],
        ];

        $sql = $driver->generate($diff);

        $this->assertCount(1, $sql['safe']);
        $this->assertStringContainsString('-- WARNING: SQLite does not support ALTER COLUMN', $sql['safe'][0]);
        $this->assertStringContainsString('"age"', $sql['safe'][0]);
        $this->assertStringContainsString('"users"', $sql['safe'][0]);
    }

    public function testSQLiteJsonStoredAsText(): void
    {
        $driver = new SqliteMigrationGenerator();
        $diff   = [
            'tables_to_create' => [
                'users' => [
                    'id'   => ['nullable' => false, 'type' => 'int',  'relation' => []],
                    'tags' => ['nullable' => true,  'type' => 'json', 'relation' => []],
                ]
            ],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [],
        ];

        $sql = $driver->generate($diff);

        $this->assertStringContainsString('"tags" TEXT NULL', $sql['safe'][0]);
    }

    public function testSQLiteBoolStoredAsInteger(): void
    {
        $driver = new SqliteMigrationGenerator();
        $diff   = [
            'tables_to_create' => [
                'users' => [
                    'id'       => ['nullable' => false, 'type' => 'int',  'relation' => []],
                    'is_admin' => ['nullable' => false, 'type' => 'bool', 'relation' => []],
                ]
            ],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [],
        ];

        $sql = $driver->generate($diff);

        $this->assertStringContainsString('"is_admin" INTEGER NOT NULL', $sql['safe'][0]);
    }

     public function testEmptyDiffProducesNoSQL(): void
    {
        $diff = [
            'tables_to_create' => [],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [],
        ];

        foreach ([
            new MySqlMigrationGenerator(),
            new PostgreSqlMigrationGenerator(),
            new SqliteMigrationGenerator(),
        ] as $driver) {
            $sql = $driver->generate($diff);
            $this->assertEmpty($sql['safe'], get_class($driver) . ' safe should be empty');
            $this->assertEmpty($sql['destructive'], get_class($driver) . ' destructive should be empty');
        }
    }
}
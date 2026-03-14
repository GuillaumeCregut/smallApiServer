<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Utils\SchemaComparator;

class SchemaComparatorTest extends TestCase
{
    private SchemaComparator $comparator;

    protected function setUp(): void
    {
        $this->comparator = new SchemaComparator();
    }

    public function testIsInSyncWhenNoDiff(): void
    {
        $diff = [
            'tables_to_create' => [],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [],
        ];
        $this->assertTrue($this->comparator->isInSync($diff));
    }

    public function testIsNotInSyncWhenDiffExists(): void
    {
        $diff = [
            'tables_to_create' => ['users' => []],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [],
        ];
        $this->assertFalse($this->comparator->isInSync($diff));
    }

    public function testDetectsTableToCreate(): void
    {
        $entitySchema = [
            'users' => [
                'id'    => ['nullable' => false, 'type' => 'int',    'relation' => []],
                'email' => ['nullable' => false, 'type' => 'string', 'relation' => []],
            ]
        ];
        $dbSchema = [];

        $diff = $this->comparator->compare($entitySchema, $dbSchema);

        $this->assertArrayHasKey('users', $diff['tables_to_create']);
        $this->assertEmpty($diff['tables_to_drop']);
        $this->assertEmpty($diff['columns_to_add']);
    }

    public function testDetectsTableToDrop(): void
    {
        $entitySchema = [];
        $dbSchema = [
            'users' => [
                'columns'      => ['id' => ['nullable' => false, 'type' => 'int', 'relation' => []]],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ]
        ];

        $diff = $this->comparator->compare($entitySchema, $dbSchema);

        $this->assertContains('users', $diff['tables_to_drop']);
        $this->assertEmpty($diff['tables_to_create']);
    }

     public function testDetectsColumnToAdd(): void
    {
        $entitySchema = [
            'users' => [
                'id'    => ['nullable' => false, 'type' => 'int',    'relation' => []],
                'email' => ['nullable' => false, 'type' => 'string', 'relation' => []],
                'phone' => ['nullable' => true,  'type' => 'string', 'relation' => []],
            ]
        ];
        $dbSchema = [
            'users' => [
                'columns' => [
                    'id'    => ['nullable' => false, 'type' => 'int',    'relation' => []],
                    'email' => ['nullable' => false, 'type' => 'string', 'relation' => []],
                ],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ]
        ];

        $diff = $this->comparator->compare($entitySchema, $dbSchema);

        $this->assertArrayHasKey('users', $diff['columns_to_add']);
        $this->assertArrayHasKey('phone', $diff['columns_to_add']['users']);
        $this->assertTrue($diff['columns_to_add']['users']['phone']['nullable']);
    }

     public function testDetectsColumnToDrop(): void
    {
        $entitySchema = [
            'users' => [
                'id'    => ['nullable' => false, 'type' => 'int',    'relation' => []],
                'email' => ['nullable' => false, 'type' => 'string', 'relation' => []],
            ]
        ];
        $dbSchema = [
            'users' => [
                'columns' => [
                    'id'      => ['nullable' => false, 'type' => 'int',    'relation' => []],
                    'email'   => ['nullable' => false, 'type' => 'string', 'relation' => []],
                    'obsolete'=> ['nullable' => true,  'type' => 'string', 'relation' => []],
                ],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ]
        ];

        $diff = $this->comparator->compare($entitySchema, $dbSchema);

        $this->assertArrayHasKey('users', $diff['columns_to_drop']);
        $this->assertContains('obsolete', $diff['columns_to_drop']['users']);
    }

    public function testDetectsTypeChange(): void
    {
        $entitySchema = [
            'users' => [
                'id'  => ['nullable' => false, 'type' => 'int',   'relation' => []],
                'age' => ['nullable' => false, 'type' => 'float', 'relation' => []],
            ]
        ];
        $dbSchema = [
            'users' => [
                'columns' => [
                    'id'  => ['nullable' => false, 'type' => 'int', 'relation' => []],
                    'age' => ['nullable' => false, 'type' => 'int', 'relation' => []],
                ],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ]
        ];

        $diff = $this->comparator->compare($entitySchema, $dbSchema);

        $this->assertArrayHasKey('users', $diff['columns_to_alter']);
        $this->assertArrayHasKey('age', $diff['columns_to_alter']['users']);
        $this->assertEquals('int',   $diff['columns_to_alter']['users']['age']['type']['from']);
        $this->assertEquals('float', $diff['columns_to_alter']['users']['age']['type']['to']);
    }

    public function testDetectsNullabilityChange(): void
    {
        $entitySchema = [
            'users' => [
                'id'    => ['nullable' => false, 'type' => 'int',    'relation' => []],
                'email' => ['nullable' => true,  'type' => 'string', 'relation' => []],
            ]
        ];
        $dbSchema = [
            'users' => [
                'columns' => [
                    'id'    => ['nullable' => false, 'type' => 'int',    'relation' => []],
                    'email' => ['nullable' => false, 'type' => 'string', 'relation' => []],
                ],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ]
        ];

        $diff = $this->comparator->compare($entitySchema, $dbSchema);

        $this->assertArrayHasKey('users', $diff['columns_to_alter']);
        $this->assertArrayHasKey('email', $diff['columns_to_alter']['users']);
        $this->assertFalse($diff['columns_to_alter']['users']['email']['nullable']['from']);
        $this->assertTrue($diff['columns_to_alter']['users']['email']['nullable']['to']);
    }

    public function testDetectsRelationChange(): void
    {
        $entitySchema = [
            'posts' => [
                'id'        => ['nullable' => false, 'type' => 'int',      'relation' => []],
                'author_id' => ['nullable' => false, 'type' => 'relation', 'relation' => ['entity' => 'authors', 'key' => 'author_id']],
            ]
        ];
        $dbSchema = [
            'posts' => [
                'columns' => [
                    'id'        => ['nullable' => false, 'type' => 'int',    'relation' => []],
                    'author_id' => ['nullable' => false, 'type' => 'int',    'relation' => []],
                ],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ]
        ];

        $diff = $this->comparator->compare($entitySchema, $dbSchema);

        $this->assertArrayHasKey('posts', $diff['columns_to_alter']);
        $this->assertArrayHasKey('author_id', $diff['columns_to_alter']['posts']);
        $this->assertArrayHasKey('relation', $diff['columns_to_alter']['posts']['author_id']);
    }

    public function testNoChangesWhenSchemasMatch(): void
    {
        $columns = [
            'id'    => ['nullable' => false, 'type' => 'int',    'relation' => []],
            'email' => ['nullable' => false, 'type' => 'string', 'relation' => []],
        ];

        $entitySchema = ['users' => $columns];
        $dbSchema = [
            'users' => [
                'columns'      => $columns,
                'primary_keys' => ['id'],
                'indexes'      => [],
            ]
        ];

        $diff = $this->comparator->compare($entitySchema, $dbSchema);

        $this->assertTrue($this->comparator->isInSync($diff));
    }

     public function testMultipleTablesPartiallyInSync(): void
    {
        $entitySchema = [
            'users' => [
                'id'    => ['nullable' => false, 'type' => 'int',    'relation' => []],
                'email' => ['nullable' => false, 'type' => 'string', 'relation' => []],
            ],
            'posts' => [
                'id'    => ['nullable' => false, 'type' => 'int',    'relation' => []],
                'title' => ['nullable' => false, 'type' => 'string', 'relation' => []],
            ],
        ];
        $dbSchema = [
            'users' => [
                'columns' => [
                    'id'    => ['nullable' => false, 'type' => 'int',    'relation' => []],
                    'email' => ['nullable' => false, 'type' => 'string', 'relation' => []],
                ],
                'primary_keys' => ['id'],
                'indexes'      => [],
            ],
            // posts table missing in DB
        ];

        $diff = $this->comparator->compare($entitySchema, $dbSchema);

        $this->assertFalse($this->comparator->isInSync($diff));
        $this->assertArrayHasKey('posts', $diff['tables_to_create']);
        $this->assertEmpty($diff['columns_to_add']);
        $this->assertEmpty($diff['columns_to_alter']);
    }
}

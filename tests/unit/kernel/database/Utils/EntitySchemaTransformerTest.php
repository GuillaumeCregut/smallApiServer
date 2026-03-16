<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Utils\EntitySchemaTransformer;

class EntitySchemaTransformerTest extends TestCase
{
    private EntitySchemaTransformer $gcTransformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gcTransformer = new EntitySchemaTransformer();
    }
    public function testEmptyEntityWillReturnNull(): void
    {
        $entity = [];
        $result = $this->gcTransformer->transform('entity', $entity);
        $this->assertNull($result);
    }

    public function testSpecialCharWillThrowException(): void
    {
        $entity = [
            'sds%$' => [],
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unauthorised characters in property name');
        $this->gcTransformer->transform('entity', $entity);
    }

    public function testPropertyMissingTypeElementWillThrowException(): void
    {
        $entity = [
            'id' => [
                'nullable' => false,
                'relation' => []
            ]
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing type');
        $result = $this->gcTransformer->transform('entity', $entity);
    }

    public function testPropertyMissingNullableElementWillThrowException(): void
    {
        $entity = [
            'id' => [
                'type' => 'int',
                'relation' => []
            ]
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing nullable');
        $result = $this->gcTransformer->transform('entity', $entity);
    }

    public function testTypeHasNoCharThrowException(): void
    {
        $entity = [
            'id' => [
                'type' => '',
                'nullable' => true,
                'relation' => []
            ]
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing type');
        $result = $this->gcTransformer->transform('entity', $entity);
    }

    public function testNullableNotBoolThrowException(): void
    {
        $entity = [
            'id' => [
                'type' => 'int',
                'nullable' => 'true',
                'relation' => []
            ]
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('wrong nullable type');
        $result = $this->gcTransformer->transform('entity', $entity);
    }

    public function testPropertyMissingRelationArrayWillThrowException(): void
    {
        $entity = [
            'id' => [
                'type' => 'int',
                'nullable' => true
            ]
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing relation array');
        $result = $this->gcTransformer->transform('entity', $entity);
    }

    public function testPropertyNotAllowedThrowException(): void
    {
        $entity = [
            'id' => [
                'type' => 'sdsdqqsdsd',
                'nullable' => true,
                'relation' => []
            ]
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('bad type');
        $result = $this->gcTransformer->transform('entity', $entity);
    }

    public function testAllAuthorizedTypesAreAccepted(): void
    {
        $types = ['string', 'int', 'float', 'datetime', 'date', 'time', 'json', 'bool'];

        foreach ($types as $type) {
            $entity = [
                'test_field' => [
                    'type' => $type,
                    'nullable' => false,
                    'relation' => []
                ]
            ];

            $result = $this->gcTransformer->transform('entity', $entity);
            $this->assertEquals($type, $result['columns']['test_field']['type']);
        }
    }

    public function testOnlyOneProperty(): void
    {
        $entity = [
            'id' => [
                'type' => 'int',
                'nullable' => false,
                'relation' => []
            ]
        ];

        $expect = [
            'columns' => [
                'id' => [
                    'nullable' => false,
                    'type' => 'int',
                ]
            ],
            'primary_keys' => [
                'id'
            ],
            'indexes' => []
        ];
        $result = $this->gcTransformer->transform('entity', $entity);
        $this->assertArrayHasKey('columns', $result);
        $this->assertArrayHasKey('primary_keys', $result);
        $this->assertArrayHasKey('indexes', $result);
        $this->assertIsArray($result['columns']);
        $this->assertIsArray($result['primary_keys']);
        $this->assertIsArray($result['indexes']);
        $this->assertEmpty($result['indexes']);
        $columns = $result['columns'];
        $this->assertArrayHasKey('id', $columns);
        $idColumn =  $columns['id'];
        $this->assertArrayHasKey('type', $idColumn);
        $this->assertArrayHasKey('nullable', $idColumn);
        $primary = $result['primary_keys'];
        $this->assertNotEmpty($primary);
        $this->assertEquals($expect, $result);
    }

    public function testWithNoIdHasNoPrimaryKey(): void
    {
        $entity = [
            'age' => [
                'type' => 'int',
                'nullable' => false,
                'relation' => []
            ]
        ];
        $result = $this->gcTransformer->transform('entity', $entity);
        $primary = $result['primary_keys'];
        $this->assertEmpty($primary);
    }

    public function testMultiplesProperties(): void
    {
        $entity = [
            'id' => [
                'type' => 'int',
                'nullable' => false,
                'relation' => []
            ],
            'name' => [
                'type' => 'string',
                'nullable' => false,
                'relation' => []
            ],
            'age' => [
                'type' => 'int',
                'nullable' => true,
                'relation' => []
            ],
        ];
        $result = $this->gcTransformer->transform('entity', $entity);
        $primary = $result['primary_keys'];
        $this->assertNotEmpty($primary);
        $this->assertEmpty($result['indexes']);
        $this->assertEquals(1, count($primary));
        $columns = $result['columns'];
        $this->assertArrayHasKey('id', $columns);
        $this->assertArrayHasKey('name', $columns);
        $this->assertArrayHasKey('age', $columns);
        $this->assertEquals('int', $columns['age']['type']);
        $this->assertEquals('string', $columns['name']['type']);
        $this->assertTrue($columns['age']['nullable']);
    }

    public function testRelationMissingEntity(): void
    {
        $entity = [
            'id' => [
                'type' => 'int',
                'nullable' => false,
                'relation' => []
            ],
            'other' => [
                'type' => 'relation',
                'nullable' => false,
                'relation' => [
                    'onUpdate' => 'CASCADE',
                    'onDelete' => 'CASCADE',
                    'key' => 'other_id'
                ]
            ],
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing relation entity');
        $this->gcTransformer->transform('entity', $entity);
    }

    public function testRelationMissingKey(): void
    {
        $entity = [
            'id' => [
                'type' => 'int',
                'nullable' => false,
                'relation' => []
            ],
            'other' => [
                'type' => 'relation',
                'nullable' => false,
                'relation' => [
                    'entity' => 'others',
                    'onUpdate' => 'CASCADE',
                    'onDelete' => 'CASCADE',
                ]
            ],
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing relation key');
        $this->gcTransformer->transform('entity', $entity);
    }

    public function testPropertyNameMissing(): void
    {
        $entity = [
            'id' => [
                'type' => 'int',
                'nullable' => false,
                'relation' => []
            ],
            '' => [
                'type' => 'relation',
                'nullable' => false,
                'relation' => [
                    'entity' => 'others',
                    'onUpdate' => 'CASCADE',
                    'onDelete' => 'CASCADE',
                ]
            ],
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing property name');
        $this->gcTransformer->transform('entity', $entity);
    }

    public function testRelationHasEmptyKeyWillThrowException(): void
    {
        $entity = [
            'id' => [
                'type' => 'int',
                'nullable' => false,
                'relation' => []
            ],
            'other' => [
                'type' => 'relation',
                'nullable' => false,
                'relation' => [
                    'entity' => 'others',
                    'onUpdate' => 'CASCADE',
                    'onDelete' => 'CASCADE',
                    'key' => ''
                ]
            ],
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing relation key');
        $this->gcTransformer->transform('entity', $entity);
    }

    public function testRelationHasNotValidOnDeleteTypeWillTrhowException(): void
    {
        $entity = [
            'id' => [
                'type' => 'int',
                'nullable' => false,
                'relation' => []
            ],
            'other' => [
                'type' => 'relation',
                'nullable' => false,
                'relation' => [
                    'entity' => 'others',
                    'onUpdate' => 'CASCADE',
                    'onDelete' => 'CASCADES',
                    'key' => 'relation_id'
                ]
            ],
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OnDelete not supported type');
        $this->gcTransformer->transform('entity', $entity);
    }

    public function testRelationHasNotValidOnUpdateTypeWillTrhowException(): void
    {
        $entity = [
            'id' => [
                'type' => 'int',
                'nullable' => false,
                'relation' => []
            ],
            'other' => [
                'type' => 'relation',
                'nullable' => false,
                'relation' => [
                    'entity' => 'others',
                    'onUpdate' => 'CASCADES',
                    'onDelete' => 'CASCADE',
                    'key' => 'relation_id'
                ]
            ],
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('onUpdate not supported type');
        $this->gcTransformer->transform('entity', $entity);
    }

    public function testConstraintsAreConvertedToUppercase(): void
    {
        $entity = [
            'id' => [
                'type' => 'int',
                'nullable' => false,
                'relation' => []
            ],
            'other' => [
                'type' => 'relation',
                'nullable' => false,
                'relation' => [
                    'entity' => 'others',
                    'onUpdate' => 'cascade', // lowercase
                    'onDelete' => 'restrict', // lowercase
                    'key' => 'other_id'
                ]
            ]
        ];

        $result = $this->gcTransformer->transform('entity', $entity);
        $this->assertEquals('CASCADE', $result['columns']['other_id']['onUpdate']);
        $this->assertEquals('RESTRICT', $result['columns']['other_id']['onDelete']);
    }

    public function testAllValidConstraintsAreAccepted(): void
    {
        $constraints = ['CASCADE', 'RESTRICT', 'SET NULL', 'NO ACTION', 'SET DEFAULT'];

        foreach ($constraints as $constraint) {
            $entity = [
                'other' => [
                    'type' => 'relation',
                    'nullable' => false,
                    'relation' => [
                        'entity' => 'others',
                        'onUpdate' => $constraint,
                        'onDelete' => $constraint,
                        'key' => 'other_id'
                    ]
                ]
            ];

            $result = $this->gcTransformer->transform('entity', $entity);
            $this->assertEquals($constraint, $result['columns']['other_id']['onDelete']);
            $this->assertEquals($constraint, $result['columns']['other_id']['onUpdate']);
        }
    }

    public function testRelationHasEmptyEntityWillThrowException(): void
    {
        $entity = [
            'id' => [
                'type' => 'int',
                'nullable' => false,
                'relation' => []
            ],
            'other' => [
                'type' => 'relation',
                'nullable' => false,
                'relation' => [
                    'entity' => '',
                    'onUpdate' => 'CASCADE',
                    'onDelete' => 'CASCADE',
                    'key' => 'other'
                ]
            ],
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing relation entity');
        $this->gcTransformer->transform('entity', $entity);
    }

    public function testEntityHasOneRelation(): void
    {
        $entity = [
            'id' => [
                'type' => 'int',
                'nullable' => false,
                'relation' => []
            ],
            'other' => [
                'type' => 'relation',
                'nullable' => false,
                'relation' => [
                    'entity' => 'others',
                    'onUpdate' => 'RESTRICT',
                    'onDelete' => 'CASCADE',
                    'key' => 'other_id'
                ]
            ],
            'age' => [
                'type' => 'int',
                'nullable' => true,
                'relation' => []
            ],
        ];
        $result = $this->gcTransformer->transform('entity', $entity);
        $this->assertNotEmpty($result['indexes']);
        $this->assertArrayHasKey('other_id', $result['columns']);
        $otherId = $result['columns']['other_id'];
        $this->assertIsArray($otherId);
        $this->assertArrayHasKey('fk', $otherId);
        $this->assertArrayHasKey('onDelete', $otherId);
        $this->assertArrayHasKey('onUpdate', $otherId);
        $this->assertEquals('int', $otherId['type']);
        $this->assertEquals('CASCADE', $otherId['onDelete']);
        $this->assertEquals('others', $otherId['fk']);
        $indexes = $result['indexes'];
        $keyRelation = "fk_other_id_others";
        $this->assertArrayHasKey($keyRelation, $indexes);
        $this->assertEquals('other_id', $indexes[$keyRelation]['columns']);
    }

    public function testEntityHasManyRelations(): void
    {
        $entity = [
            'id' => [
                'type' => 'int',
                'nullable' => false,
                'relation' => []
            ],
            'other' => [
                'type' => 'relation',
                'nullable' => false,
                'relation' => [
                    'entity' => 'others',
                    'onUpdate' => 'RESTRICT',
                    'onDelete' => 'CASCADE',
                    'key' => 'other_id'
                ]
            ],
            'another' => [
                'type' => 'relation',
                'nullable' => false,
                'relation' => [
                    'entity' => 'anothers',
                    'onUpdate' => 'RESTRICT',
                    'onDelete' => 'CASCADE',
                    'key' => 'another_id'
                ]
            ],
            'age' => [
                'type' => 'int',
                'nullable' => true,
                'relation' => []
            ],
        ];
        $result = $this->gcTransformer->transform('entity', $entity);
        $this->assertNotEmpty($result['indexes']);
        $this->assertArrayHasKey('other_id', $result['columns']);
        $otherId = $result['columns']['other_id'];
        $this->assertIsArray($otherId);
        $this->assertArrayHasKey('fk', $otherId);
        $this->assertArrayHasKey('onDelete', $otherId);
        $this->assertArrayHasKey('onUpdate', $otherId);
        $this->assertEquals('int', $otherId['type']);
        $this->assertEquals('CASCADE', $otherId['onDelete']);
        $this->assertEquals('others', $otherId['fk']);
        $this->assertEquals(2, count($result['indexes']));
        $indexes = $result['indexes'];
        $keyRelation = "fk_other_id_others";
        $this->assertArrayHasKey($keyRelation, $indexes);
        $this->assertEquals('other_id', $indexes[$keyRelation]['columns']);
    }

    public function testTransformResetsBetweenCalls(): void
    {
        $entity1 = [
            'id' => ['type' => 'int', 'nullable' => false, 'relation' => []],
            'other' => [
                'type' => 'relation',
                'nullable' => false,
                'relation' => [
                    'entity' => 'others',
                    'onUpdate' => 'RESTRICT',
                    'onDelete' => 'CASCADE',
                    'key' => 'other_id'
                ]
            ]
        ];

        $result1 = $this->gcTransformer->transform('entity1', $entity1);
        $this->assertCount(2, $result1['columns']);
        $this->assertCount(1, $result1['indexes']);

        $entity2 = [
            'name' => ['type' => 'string', 'nullable' => true, 'relation' => []]
        ];

        $result2 = $this->gcTransformer->transform('entity2', $entity2);

        $this->assertCount(1, $result2['columns']);
        $this->assertArrayNotHasKey('id', $result2['columns']);
        $this->assertArrayNotHasKey('other_id', $result2['columns']);
        $this->assertEmpty($result2['primary_keys']);
        $this->assertEmpty($result2['indexes']);
    }

    public function testIndexAndColumnUseRelationKey(): void
    {
        $entity = [
            'id' => [
                'type' => 'int',
                'nullable' => false,
                'relation' => []
            ],
            'user' => [
                'type' => 'relation',
                'nullable' => false,
                'relation' => [
                    'entity' => 'users',
                    'onUpdate' => 'CASCADE',
                    'onDelete' => 'CASCADE',
                    'key' => 'custom_user_id'
                ]
            ]
        ];

        $result = $this->gcTransformer->transform('entity', $entity);

        $this->assertArrayHasKey('custom_user_id', $result['columns']);
        $this->assertArrayNotHasKey('user_id', $result['columns']);
        $expectedIndexName = 'fk_custom_user_id_users';
        $this->assertArrayHasKey($expectedIndexName, $result['indexes']);
        $this->assertEquals('custom_user_id', $result['indexes'][$expectedIndexName]['columns']);
    }
}
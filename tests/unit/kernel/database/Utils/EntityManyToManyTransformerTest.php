<?php

use App\Kernel\Connector\Utils\EntityManyToManyTranformer;
use PHPUnit\Framework\TestCase;

class EntityManyToManyTransformerTest extends TestCase
{
    private EntityManyToManyTranformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new EntityManyToManyTranformer();
    }

    public function testReturnEmptyArrayOnNoInputs(): void
    {
        $result = $this->transformer->transform([]);
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testReturnEmptyArrayOnEmptyProperty(): void
    {
        $values = [
            'entity' => []
        ];
        $result = $this->transformer->transform($values);
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testThrowExceptionOnMissingTableName(): void
    {
        $values = [
            'entity' => [
                'courses' => [
                    'colOwner' => 'entity1_id',
                    'colTarget' => 'entity2_id',
                    'tableOwner' => 'EntityOwnerManyToMany',
                    'tableTarget' => 'EntityInversedManyToMany',
                ]
            ]
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity entity property courses missing tableName');
        $this->transformer->transform($values);
    }

    public function testThrowExceptionOnMissingColOwner(): void
    {
        $values = [
            'entity' => [
                'courses' => [
                    'tableName' => 'entity1_entity2',
                    'colTarget' => 'entity2_id',
                    'tableOwner' => 'EntityOwnerManyToMany',
                    'tableTarget' => 'EntityInversedManyToMany',
                ]
            ]
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity entity property courses missing colOwner');
        $this->transformer->transform($values);
    }

    public function testThrowExceptionOnMissingColTarget(): void
    {
        $values = [
            'entity' => [
                'courses' => [
                    'tableName' => 'entity1_entity2',
                    'colOwner' => 'entity1_id',
                    'tableOwner' => 'EntityOwnerManyToMany',
                    'tableTarget' => 'EntityInversedManyToMany',
                ]
            ]
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity entity property courses missing colTarget');
        $this->transformer->transform($values);
    }

    public function testThrowExceptionOnMissingTableOwner(): void
    {
        $values = [
            'entity' => [
                'courses' => [
                    'tableName' => 'entity1_entity2',
                    'colOwner' => 'entity1_id',
                    'colTarget' => 'entity2_id',
                    'tableTarget' => 'EntityInversedManyToMany',
                ]
            ]
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity entity property courses missing tableOwner');
        $this->transformer->transform($values);
    }

    public function testThrowExceptionOnMissingTableTarget(): void
    {
        $values = [
            'entity' => [
                'courses' => [
                    'tableName' => 'entity1_entity2',
                    'colOwner' => 'entity1_id',
                    'colTarget' => 'entity2_id',
                    'tableOwner' => 'EntityInversedManyToMany',
                ]
            ]
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity entity property courses missing tableTarget');
        $this->transformer->transform($values);
    }

    public function testWillReturnFormattedArray(): void
    {
        $values = [
            'entity' => [
                'courses' => [
                    'tableName' => 'entity1_entity2',
                    'colOwner' => 'entity1_id',
                    'colTarget' => 'entity2_id',
                    'tableOwner' => 'entity_owner_many_to_many',
                    'tableTarget' => 'entity_inversed_many_to_many',
                ]
            ]
        ];
        $expected = [
            0 => [
                'pivotTable' => 'entity1_entity2',
                'ownerTable' => 'entity_owner_many_to_many',
                'targetTable' => 'entity_inversed_many_to_many',
                'ownerCol' => 'entity1_id',
                'targetCol' => 'entity2_id',
            ]
        ];
        $result = $this->transformer->transform($values);
        $this->assertSame($expected, $result);
    }

    public function testDoubleInformationHasOnlyOne(): void
    {
        $values = [
            'entity' => [
                'courses' => [
                    'tableName' => 'entity1_entity2',
                    'colOwner' => 'entity1_id',
                    'colTarget' => 'entity2_id',
                    'tableOwner' => 'entity_owner_many_to_many',
                    'tableTarget' => 'entity_inversed_many_to_many',
                ]
            ],
            'entity2' => [
                'courses' => [
                    'tableName' => 'entity1_entity2',
                    'colOwner' => 'entity2_id',
                    'colTarget' => 'entity1_id',
                    'tableOwner' => 'entity_inversed_many_to_many',
                    'tableTarget' => 'entity_owner_many_to_many',
                ]
            ]
        ];

        $expected = [
            0 => [
                'pivotTable' => 'entity1_entity2',
                'ownerTable' => 'entity_owner_many_to_many',
                'targetTable' => 'entity_inversed_many_to_many',
                'ownerCol' => 'entity1_id',
                'targetCol' => 'entity2_id',
            ]
        ];
        $result = $this->transformer->transform($values);
        $this->assertSame($expected, $result);
    }

    public function testResetOnScanning(): void
    {
        $values = [
            'entity' => [
                'courses' => [
                    'tableName' => 'entity1_entity2',
                    'colOwner' => 'entity1_id',
                    'colTarget' => 'entity2_id',
                    'tableOwner' => 'entity_owner_many_to_many',
                    'tableTarget' => 'entity_inversed_many_to_many',
                ]
            ],
            'entity2' => [
                'courses' => [
                    'tableName' => 'entity1_entity2',
                    'colOwner' => 'entity2_id',
                    'colTarget' => 'entity1_id',
                    'tableOwner' => 'entity_inversed_many_to_many',
                    'tableTarget' => 'entity_owner_many_to_many',
                ]
            ]
        ];

        $expected = [
            0 => [
                'pivotTable' => 'entity1_entity2',
                'ownerTable' => 'entity_owner_many_to_many',
                'targetTable' => 'entity_inversed_many_to_many',
                'ownerCol' => 'entity1_id',
                'targetCol' => 'entity2_id',
            ]
        ];
        $result = $this->transformer->transform($values);
        $values = [
            'entity2' => [
                'courses' => [
                    'tableName' => 'entity1_entity2',
                    'colOwner' => 'entity2_id',
                    'colTarget' => 'entity1_id',
                    'tableOwner' => 'entity_inversed_many_to_many',
                    'tableTarget' => 'entity_owner_many_to_many',
                ]
            ]
        ];
        $expected = [
            0 => [
                'pivotTable' => 'entity1_entity2',
                'ownerTable' => 'entity_inversed_many_to_many',
                'targetTable' => 'entity_owner_many_to_many',
                'ownerCol' => 'entity2_id',
                'targetCol' => 'entity1_id',
            ]
        ];
        $result = $this->transformer->transform($values);
        $this->assertSame($expected, $result);
    }

    public function testReturnAllUniqueTable(): void
    {
        $values = [
            'entity' => [
                'courses' => [
                    'tableName' => 'entity1_entity2',
                    'colOwner' => 'entity1_id',
                    'colTarget' => 'entity2_id',
                    'tableOwner' => 'entity_owner_many_to_many',
                    'tableTarget' => 'entity_inversed_many_to_many',
                ]
            ],
            'entity2' => [
                'courses' => [
                    'tableName' => 'entity3_entity4',
                    'colOwner' => 'entity3_id',
                    'colTarget' => 'entity4_id',
                    'tableOwner' => 'entity_inversed_many_to_many',
                    'tableTarget' => 'entity_owner_many_to_many',
                ]
            ]
        ];

        $expected = [
            0 => [
                'pivotTable' => 'entity1_entity2',
                'ownerTable' => 'entity_owner_many_to_many',
                'targetTable' => 'entity_inversed_many_to_many',
                'ownerCol' => 'entity1_id',
                'targetCol' => 'entity2_id',
            ],
            1 =>[
                'pivotTable' => 'entity3_entity4',
                'ownerTable' => 'entity_inversed_many_to_many',
                'targetTable' => 'entity_owner_many_to_many',
                'ownerCol' => 'entity3_id',
                'targetCol' => 'entity4_id',
            ]
        ];
        $result = $this->transformer->transform($values);
        $this->assertSame($expected, $result);
    }
}
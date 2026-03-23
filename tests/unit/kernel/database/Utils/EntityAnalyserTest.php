<?php

use App\Security\User;
use PHPUnit\Framework\TestCase;
use App\Kernel\Files\FileUpload;
use App\Kernel\Connector\Datas\LazyBag;
use App\Kernel\Connector\Attributes\Nullable;
use App\Kernel\Connector\Attributes\ManyToOne;
use App\Kernel\Connector\Attributes\NotStored;
use App\Kernel\Connector\Attributes\OneToMany;
use App\Kernel\Connector\Utils\EntityAnalyzer;
use App\Kernel\Connector\Attributes\ManyToMany;
use App\Kernel\Connector\Interfaces\EntityInterface;

class EntityAnalyserTest extends TestCase
{
    public function testWillThrowExceptionsOnEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntityAnalyzer::getStoredProperties('');
    }

    public function testWillThrowExceptionsOnNonExistingClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntityAnalyzer::getStoredProperties('FooBarBaz');
    }

    public function testWillThrowExceptionsOnNonExistingClassManyToMany(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntityAnalyzer::getManyToManyRelations('FooBarBaz');
    }


    public function testWillThrowExceptionOnNonEntityInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntityAnalyzer::getStoredProperties(TestCase::class);
    }

    public function testWillThrowExceptionOnNonEntityInterfaceManyToMany(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntityAnalyzer::getManyToManyRelations(TestCase::class);
    }

    public function testWillReturnStoredPropertyWithAll(): void
    {
        $expect = [
            'id' => [
                'nullable' => false,
                'type' => 'int',
                'relation' => []
            ]
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityAnlyzeWithStoredProps::class);
        $this->assertSame($expect, $result);
    }


    public function testWillReturnStoredPropertyWithNullable(): void
    {
        $expect = [
            'id' => [
                'nullable' => false,
                'type' => 'int',
                'relation' => []
            ],
            'name' => [
                'nullable' => true,
                'type' => 'string',
                'relation' => []
            ]
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityAnlyzeWithNullProps::class);
        $this->assertSame($expect, $result);
    }

    public function testWillReturnStoredPropertyWitNotStored(): void
    {
        $expect = [
            'id' => [
                'nullable' => false,
                'type' => 'int',
                'relation' => []
            ]
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityAnlyzeWithNotStored::class);
        $this->assertEquals($expect, $result);
    }

    public function testWillReturnStoredPropertyWitMixed(): void
    {
        $expect = [
            'id' => [
                'nullable' => false,
                'type' => 'int',
                'relation' => []
            ],
            'name' => [
                'nullable' => true,
                'type' => 'string',
                'relation' => []
            ],
            'values' => [
                'nullable' => false,
                'type' => 'json',
                'relation' => []
            ],
            'price' => [
                'nullable' => false,
                'type' => 'float',
                'relation' => []
            ],
            'inStock' => [
                'nullable' => false,
                'type' => 'bool',
                'relation' => []
            ]
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityAnlyzeWithManyProps::class);
        $this->assertSame($expect, $result);
    }

    public function testWillReturnRelationPropertyWithAll(): void
    {
        $expect = [
            'id' => [
                'nullable' => false,
                'type' => 'int',
                'relation' => []
            ],
            'entity' => [
                'nullable' => false,
                'type' => 'relation',
                'relation' => [
                    'entity' => 'EntityAnlyzeWithStoredProps',
                    'key' => 'entityId',
                    'onUpdate' => 'RESTRICT',
                    'onDelete' => 'CASCADE'
                ]
            ]
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityAnlyzeWithRelationProps::class);
        $this->assertEquals($expect, $result);
    }

    public function testWillReturnStoredPropertyWithNullableRelation(): void
    {
        $expect = [
            'id' => [
                'nullable' => false,
                'type' => 'int',
                'relation' => []
            ],
            'entity' => [
                'nullable' => true,
                'type' => 'relation',
                'relation' => [
                    'entity' => 'EntityAnlyzeWithStoredProps',
                    'key' => 'entityId',
                    'onUpdate' => 'CASCADE',
                    'onDelete' => 'CASCADE'
                ]
            ]
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityAnalyzeWithNullRelationProps::class);
        $this->assertEquals($expect, $result);
    }

    public function testWillReturnCorrectKeyForRelation(): void
    {
        $expect = [
            'id' => [
                'nullable' => false,
                'type' => 'int',
                'relation' => []
            ],
            'relatedItem' => [
                'nullable' => false,
                'type' => 'relation',
                'relation' => [
                    'entity' => 'EntityAnlyzeWithStoredProps',
                    'key' => 'relatedItemId',
                    'onUpdate' => 'RESTRICT',
                    'onDelete' => 'RESTRICT'
                ]
            ]
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityAnalyzeWithNamedRelation::class);
        $this->assertEquals($expect, $result);
    }

    public function testWillThrowExceptionOnNonFinalClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $result = EntityAnalyzer::getStoredProperties(EntityAnalyzeNonFinal::class);
    }

    public function testWillHandleMultipleRelations(): void
    {
        $expect = [
            'id' => [
                'nullable' => false,
                'type' => 'int',
                'relation' => []
            ],
            'relatedItem' => [
                'nullable' => false,
                'type' => 'relation',
                'relation' => [
                    'entity' => 'EntityAnlyzeWithStoredProps',
                    'key' => 'relatedItemId',
                    'onUpdate' => 'RESTRICT',
                    'onDelete' => 'CASCADE'
                ]
            ],
            'other' => [
                'nullable' => false,
                'type' => 'relation',
                'relation' => [
                    'entity' => 'EntityAnalyzeWithNamedRelation',
                    'key' => 'otherId',
                    'onUpdate' => 'CASCADE',
                    'onDelete' => 'RESTRICT'
                ]
            ]
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityToAnalyseWithTwoRelations::class);
        $this->assertEquals($expect, $result);
    }

    public function testFileIsConvertedToString(): void
    {
        $expect = [
            'id' => [
                'nullable' => false,
                'type' => 'int',
                'relation' => []
            ],
            'avatar' => [
                'nullable' => false,
                'type' => 'string',
                'relation' => []
            ]
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityAnalyzeWithFile::class);
        $this->assertSame($expect, $result);
    }

    public function testWillHandleMultipleRelationsTranslated(): void
    {
        $expect = [
            'id' => [
                'nullable' => false,
                'type' => 'int',
                'relation' => []
            ],
            'related_item' => [
                'nullable' => false,
                'type' => 'relation',
                'relation' => [
                    'entity' => 'entity_anlyze_with_stored_propss',
                    'key' => 'related_item_id',
                    'onDelete' => 'CASCADE',
                    'onUpdate' => 'RESTRICT',
                ]
            ],
            'other' => [
                'nullable' => false,
                'type' => 'relation',
                'relation' => [
                    'entity' => 'entity_analyze_with_named_relations',
                    'key' => 'other_id',
                    'onUpdate' => 'CASCADE',
                    'onDelete' => 'RESTRICT',
                ]
            ]
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityToAnalyseWithTwoRelations::class, true);
        $this->assertEquals($expect, $result);
    }

    public function testWillReturnStoredPropertyWitMixedTranslated(): void
    {
        $expect = [
            'id' => [
                'nullable' => false,
                'type' => 'int',
                'relation' => []
            ],
            'name' => [
                'nullable' => true,
                'type' => 'string',
                'relation' => []
            ],
            'values' => [
                'nullable' => false,
                'type' => 'json',
                'relation' => []
            ],
            'price' => [
                'nullable' => false,
                'type' => 'float',
                'relation' => []
            ],
            'in_stock' => [
                'nullable' => false,
                'type' => 'bool',
                'relation' => []
            ]
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityAnlyzeWithManyProps::class, true);
        $this->assertSame($expect, $result);
    }

    public function testWillResolvePropertyWitMixedTranslated(): void
    {
        $expect = [
            'id' => [
                'nullable' => false,
                'type' => 'int',
                'relation' => []
            ],

            'related_item' => [
                'nullable' => false,
                'type' => 'relation',
                'relation' => [
                    'entity' => 'users',
                    'key' => 'related_item_id',
                    'onDelete' => 'CASCADE',
                    'onUpdate' => 'RESTRICT',
                ]
            ],
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityWithRelationInFrameWork::class, true);
        $this->assertSame($expect, $result);
    }

    public function testOneToManyDoesNotAppear(): void
    {
        $expect = [
            'id' => [
                'nullable' => false,
                'type' => 'int',
                'relation' => []
            ],
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityAnalyzeWithOneToMany::class);
        $this->assertSame($expect, $result);
    }

    public function testGetAllInformationOntable(): void
    {
        $expect = [
            'entitiesInversed' => [
                'tableName' => 'entity1_entity2',
                'colOwner' => 'entity1_id',
                'colTarget' => 'entity2_id',
                'tableOwner' => 'EntityOwnerManyToMany',
                'tableTarget' => 'EntityInversedManyToMany',
            ]
        ];
        $result = EntityAnalyzer::getManyToManyRelations(EntityOwnerManyToMany::class);
        $this->assertSame($expect, $result);
    }

    public function testReverseGetInformations(): void
    {
        $expect = [
            'entityOwner' => [
                'tableName' => 'entity1_entity2',
                'colOwner' => 'entity2_id',
                'colTarget' => 'entity1_id',
                'tableOwner' => 'EntityInversedManyToMany',
                'tableTarget' => 'EntityOwnerManyToMany',
            ]
        ];
        $result = EntityAnalyzer::getManyToManyRelations(EntityInversedManyToMany::class);
        $this->assertSame($expect, $result);
    }

    public function testGetAllTranslatedOwner(): void
    {
        $expect = [
            'entities_inversed' => [
                'tableName' => 'entity1_entity2',
                'colOwner' => 'entity1_id',
                'colTarget' => 'entity2_id',
                'tableOwner' => 'entity_owner_many_to_manys',
                'tableTarget' => 'entity_inversed_many_to_manys',
            ]
        ];
        $result = EntityAnalyzer::getManyToManyRelations(EntityOwnerManyToMany::class, true);
        $this->assertSame($expect, $result);
    }

    public function testReverseGetInformationsTranslated(): void
    {
        $expect = [
            'entity_owner' => [
                'tableName' => 'entity1_entity2',
                'colOwner' => 'entity2_id',
                'colTarget' => 'entity1_id',
                'tableOwner' => 'entity_inversed_many_to_manys',
                'tableTarget' => 'entity_owner_many_to_manys',
            ]
        ];
        $result = EntityAnalyzer::getManyToManyRelations(EntityInversedManyToMany::class, true);
        $this->assertSame($expect, $result);
    }

    public function testWithNoManyToManyRelationWillReturnEmpty(): void
    {
        $result = EntityAnalyzer::getManyToManyRelations(EntityWithRelationInFrameWork::class);
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testManyToManyDoesNotAppearInStoredProperties(): void
    {
        $expect = [
            'id' => [
                'nullable' => false,
                'type' => 'int',
                'relation' => []
            ],
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityOwnerManyToMany::class);
        $this->assertSame($expect, $result);
    }
}

final class EntityWithRelationInFrameWork implements EntityInterface
{
    private ?int $id = null;
    #[ManyToOne(inversedBy: 'field', targetEntity: EntityAnlyzeWithStoredProps::class, onDelete: 'CASCADE', onUpdate: 'RESTRICT')]
    private ?User $relatedItem = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public static function getRepository(): ?string
    {
        return '';
    }
}
final class EntityAnalyzeWithOneToMany implements EntityInterface
{
    private ?int $id = null;
    #[OneToMany()]
    private ?array $avatar = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public static function getRepository(): ?string
    {
        return '';
    }
}


final class EntityAnalyzeWithFile implements EntityInterface
{
    private ?int $id = null;
    private ?FileUpload $avatar = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public static function getRepository(): ?string
    {
        return '';
    }
}

final class EntityToAnalyseWithTwoRelations implements EntityInterface
{
    private ?int $id = null;

    #[ManyToOne(inversedBy: 'field', targetEntity: EntityAnlyzeWithStoredProps::class, onDelete: 'CASCADE', onUpdate: 'RESTRICT')]
    private ?EntityAnlyzeWithStoredProps $relatedItem = null;

    #[ManyToOne(inversedBy: 'field', targetEntity: EntityAnalyzeWithNamedRelation::class, onUpdate: 'CASCADE', onDelete: 'RESTRICT')]
    private ?EntityAnalyzeWithNamedRelation $other = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public static function getRepository(): ?string
    {
        return '';
    }
}



class EntityAnalyzeNonFinal implements EntityInterface
{
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public static function getRepository(): ?string
    {
        return '';
    }
}

final class EntityAnalyzeWithNamedRelation implements EntityInterface
{
    private ?int $id = null;

    #[ManyToOne(inversedBy: 'field', targetEntity: EntityAnlyzeWithStoredProps::class)]
    private ?EntityAnlyzeWithStoredProps $relatedItem = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public static function getRepository(): ?string
    {
        return '';
    }
}



final class EntityAnlyzeWithStoredProps implements EntityInterface
{
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public static function getRepository(): ?string
    {
        return '';
    }
}

final class EntityAnlyzeWithRelationProps implements EntityInterface
{
    private ?int $id = null;

    #[ManyToOne(inversedBy: 'field', targetEntity: EntityAnlyzeWithStoredProps::class, onUpdate: 'RESTRICT', onDelete: 'CASCADE')]
    private ?EntityAnlyzeWithStoredProps $entity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public static function getRepository(): ?string
    {
        return '';
    }
}

final class EntityAnalyzeWithNullRelationProps implements EntityInterface
{
    private ?int $id = null;
    #[Nullable]
    #[ManyToOne(inversedBy: 'field', targetEntity: EntityAnlyzeWithStoredProps::class, onDelete: 'CASCADE', onUpdate: 'CASCADE')]
    private ?EntityAnlyzeWithStoredProps $entity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public static function getRepository(): ?string
    {
        return '';
    }
}

final class EntityAnlyzeWithNullProps implements EntityInterface
{
    private ?int $id = null;
    #[Nullable]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public static function getRepository(): ?string
    {
        return '';
    }
}

final class EntityAnlyzeWithNotStored implements EntityInterface
{
    private ?int $id = null;
    #[NotStored]
    private ?string $firstname = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public static function getRepository(): ?string
    {
        return '';
    }
}


final class EntityAnlyzeWithManyProps implements EntityInterface
{
    private ?int $id = null;
    #[NotStored]
    private ?string $firstname = null;
    #[Nullable]
    private ?string $name = null;
    private array $values = [];
    private ?float $price = null;
    private ?bool $inStock = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public static function getRepository(): ?string
    {
        return '';
    }
}

final class EntityOwnerManyToMany implements EntityInterface
{
    private ?int $id = null;

    #[ManyToMany(
        inversedBy: 'entitiesOwner',
        targetEntity: EntityInversedManyToMany::class,
        targetColumn: 'entity2_id',
        ownerColumn: 'entity1_id',
        pivotTable: 'entity1_entity2'
    )]
    private ?LazyBag $entitiesInversed = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public static function getRepository(): ?string
    {
        return '';
    }
}

final class EntityInversedManyToMany implements EntityInterface
{
    private ?int $id = null;

    #[ManyToMany(
        targetEntity: EntityOwnerManyToMany::class,
        mappedBy: 'EntitiesInversed',
        targetColumn: 'entity1_id',
        ownerColumn: 'entity2_id',
        pivotTable: 'entity1_entity2'
    )]
    private ?LazyBag $entityOwner = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public static function getRepository(): ?string
    {
        return '';
    }
}

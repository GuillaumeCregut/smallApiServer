<?php

use App\Kernel\Connector\Attributes\ManyToOne;
use App\Kernel\Connector\Attributes\NotStored;
use App\Kernel\Connector\Attributes\Nullable;
use App\Kernel\Connector\Interfaces\EntityInterface;
use App\Kernel\Connector\Utils\EntityAnalyzer;
use App\Kernel\Files\FileUpload;
use PHPUnit\Framework\TestCase;

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


    public function testWillThrowExceptionOnNonEntityInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntityAnalyzer::getStoredProperties(TestCase::class);
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
        $this->assertSame($expect, $result);
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
                'type' => 'array',
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
                    'key' => 'entityId'
                ]
            ]
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityAnlyzeWithRelationProps::class);
        $this->assertSame($expect, $result);
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
                    'key' => 'entityId'
                ]
            ]
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityAnalyzeWithNullRelationProps::class);
        $this->assertSame($expect, $result);
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
                    'key' => 'relatedItemId'
                ]
            ]
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityAnalyzeWithNamedRelation::class);
        $this->assertSame($expect, $result);
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
                    'key' => 'relatedItemId'
                ]
            ],
            'other' => [
                'nullable' => false,
                'type' => 'relation',
                'relation' => [
                    'entity' => 'EntityAnalyzeWithNamedRelation',
                    'key' => 'otherId'
                ]
            ]
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityToAnalyseWithTwoRelations::class);
        $this->assertSame($expect, $result);
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
                    'key' => 'related_item_id'
                ]
            ],
            'other' => [
                'nullable' => false,
                'type' => 'relation',
                'relation' => [
                    'entity' => 'entity_analyze_with_named_relations',
                    'key' => 'other_id'
                ]
            ]
        ];
        $result = EntityAnalyzer::getStoredProperties(EntityToAnalyseWithTwoRelations::class, true);
        $this->assertSame($expect, $result);
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
                'type' => 'array',
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

    #[ManyToOne(inversedBy: 'field', targetEntity: EntityAnlyzeWithStoredProps::class)]
    private ?EntityAnlyzeWithStoredProps $relatedItem = null;

    #[ManyToOne(inversedBy: 'field', targetEntity: EntityAnalyzeWithNamedRelation::class)]
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

    #[ManyToOne(inversedBy: 'field', targetEntity: EntityAnlyzeWithStoredProps::class)]
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
    #[ManyToOne(inversedBy: 'field', targetEntity: EntityAnlyzeWithStoredProps::class)]
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

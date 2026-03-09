<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Form\Caster\DataCaster;
use App\Kernel\Connector\Interfaces\EntityInterface;

class DataCasterTest extends TestCase
{
    public function testCastsStringToInt(): void
    {
        $result = DataCaster::cast(CastableEntity::class, ['age' => '42']);
        $this->assertSame(42, $result['age']);
    }

    public function testIntAlreadyIntIsUntouched(): void
    {
        $result = DataCaster::cast(CastableEntity::class, ['age' => 42]);
        $this->assertSame(42, $result['age']);
    }

    public function testCastsFloatStringToInt(): void
    {
        $result = DataCaster::cast(CastableEntity::class, ['age' => '42.9']);
        $this->assertSame(42, $result['age']);
    }

    public function testCastsStringToFloat(): void
    {
        $result = DataCaster::cast(CastableEntity::class, ['price' => '9.99']);
        $this->assertSame(9.99, $result['price']);
    }

    public function testFloatAlreadyFloatIsUntouched(): void
    {
        $result = DataCaster::cast(CastableEntity::class, ['price' => 9.99]);
        $this->assertSame(9.99, $result['price']);
    }

    public function testCastsStringOneToTrue(): void
    {
        $result = DataCaster::cast(CastableEntity::class, ['active' => '1']);
        $this->assertTrue($result['active']);
    }

    public function testCastsStringZeroToFalse(): void
    {
        $result = DataCaster::cast(CastableEntity::class, ['active' => '0']);
        $this->assertFalse($result['active']);
    }

    public function testCastsStringTrueToTrue(): void
    {
        $result = DataCaster::cast(CastableEntity::class, ['active' => 'true']);
        $this->assertTrue($result['active']);
    }

    public function testCastsStringFalseToFalse(): void
    {
        $result = DataCaster::cast(CastableEntity::class, ['active' => 'false']);
        $this->assertFalse($result['active']);
    }

    public function testBoolAlreadyBoolIsUntouched(): void
    {
        $result = DataCaster::cast(CastableEntity::class, ['active' => true]);
        $this->assertTrue($result['active']);
    }

    public function testStringRemainsString(): void
    {
        $result = DataCaster::cast(CastableEntity::class, ['name' => 'John']);
        $this->assertSame('John', $result['name']);
    }

    public function testCastsIntToString(): void
    {
        $result = DataCaster::cast(CastableEntity::class, ['name' => 42]);
        $this->assertSame('42', $result['name']);
    }

    public function testNullIsUntouchedOnNullableProperty(): void
    {
        $result = DataCaster::cast(CastableEntity::class, ['nullable' => null]);
        $this->assertNull($result['nullable']);
    }

    public function testStringValueOnNullablePropertyIsCast(): void
    {
        $result = DataCaster::cast(CastableEntity::class, ['nullable' => 'hello']);
        $this->assertSame('hello', $result['nullable']);
    }

    public function testUnknownKeyIsUntouched(): void
    {
        // 'ghost' does not exist on the entity → value passes through as-is
        $result = DataCaster::cast(CastableEntity::class, ['ghost' => '123']);
        $this->assertSame('123', $result['ghost']);
    }

    public function testCastsMultipleFieldsAtOnce(): void
    {
        $result = DataCaster::cast(CastableEntity::class, [
            'age'    => '25',
            'price'  => '19.99',
            'active' => '1',
            'name'   => 'Jane',
        ]);

        $this->assertSame(25,     $result['age']);
        $this->assertSame(19.99,  $result['price']);
        $this->assertTrue($result['active']);
        $this->assertSame('Jane', $result['name']);
    }

    public function testEmptyArrayReturnsEmptyArray(): void
    {
        $result = DataCaster::cast(CastableEntity::class, []);
        $this->assertSame([], $result);
    }

    public function testObjectPropertyIsUntouched(): void
    {
        $related = new RelatedEntity();

        $result = DataCaster::cast(EntityWithRelation::class, ['relation' => $related]);

        $this->assertSame($related, $result['relation']);
    }
}

class CastableEntity implements EntityInterface
{
    private int $id;
    private int $age;
    private float $price;
    private bool $active;
    private string $name;
    private ?string $nullable;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getAge(): int
    {
        return $this->age;
    }
    public function setAge(int $age): self
    {
        $this->age = $age;
        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }
    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getNullable(): ?string
    {
        return $this->nullable;
    }
    public function setNullable(?string $nullable): self
    {
        $this->nullable = $nullable;
        return $this;
    }

    public static function getRepository(): string
    {
        return '';
    }
}

class RelatedEntity implements EntityInterface
{
    private int $id;
    public static function getRepository(): string
    {
        return '';
    }
    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
}

class EntityWithRelation implements EntityInterface
{
    private int $id;
    private RelatedEntity $relation;
    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public static function getRepository(): string
    {
        return '';
    }
}

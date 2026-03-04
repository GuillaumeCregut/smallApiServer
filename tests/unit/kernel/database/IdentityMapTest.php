<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Management\IdentityMap;
use App\Kernel\Connector\Interfaces\EntityInterface;

class IdentityMapTest extends TestCase
{
 private IdentityMap $map;

    protected function setUp(): void
    {
        $this->map = new IdentityMap();
    }

    private function makeEntity(?int $id): StubEntity
    {
        $e = new StubEntity();
        $e->setId($id);
        return $e;
    }

    private function makeOtherEntity(?int $id): OtherStubEntity
    {
        $e = new OtherStubEntity();
        $e->setId($id);
        return $e;
    }

    public function testGetReturnsNullWhenEntityNotRegistered(): void
    {
        $result = $this->map->get(StubEntity::class, 1);
        $this->assertNull($result);
    }

    public function testGetReturnsEntityAfterRegistration(): void
    {
        $entity = $this->makeEntity(42);
        $this->map->getOrRegister($entity);

        $result = $this->map->get(StubEntity::class, 42);
        $this->assertSame($entity, $result);
    }

    public function testGetDistinguishesByClass(): void
    {
        $a = $this->makeEntity(1);
        $b = $this->makeOtherEntity(1);

        $this->map->getOrRegister($a);
        $this->map->getOrRegister($b);

        $this->assertSame($a, $this->map->get(StubEntity::class, 1));
        $this->assertSame($b, $this->map->get(OtherStubEntity::class, 1));
    }

    public function testGetOrRegisterReturnsIncomingEntityWhenNotKnown(): void
    {
        $entity = $this->makeEntity(10);
        $result = $this->map->getOrRegister($entity);

        $this->assertSame($entity, $result);
    }

     public function testGetOrRegisterReturnsExistingInstanceOnDuplicate(): void
    {
        $first  = $this->makeEntity(10);
        $second = $this->makeEntity(10); // same id, different object

        $this->map->getOrRegister($first);
        $result = $this->map->getOrRegister($second);

        $this->assertSame($first, $result);
        $this->assertNotSame($second, $result);
    }

    public function testGetOrRegisterPassesThroughEntityWithNullId(): void
    {
        $entity = $this->makeEntity(null);
        $result = $this->map->getOrRegister($entity);

        // Cannot key by id — returned as-is, not stored
        $this->assertSame($entity, $result);
        $this->assertNull($this->map->get(StubEntity::class, 0));
    }

    public function testHasReturnsFalseForUnregisteredEntity(): void
    {
        $entity = $this->makeEntity(5);
        $this->assertFalse($this->map->has($entity));
    }

    public function testHasReturnsTrueAfterRegistration(): void
    {
        $entity = $this->makeEntity(5);
        $this->map->getOrRegister($entity);

        $this->assertTrue($this->map->has($entity));
    }

    public function testHasReturnsFalseForEntityWithNullId(): void
    {
        $entity = $this->makeEntity(null);
        $this->assertFalse($this->map->has($entity));
    }

    public function testDetachRemovesEntityFromMap(): void
    {
        $entity = $this->makeEntity(7);
        $this->map->getOrRegister($entity);
        $this->map->detach($entity);

        $this->assertNull($this->map->get(StubEntity::class, 7));
        $this->assertFalse($this->map->has($entity));
    }

    public function testDetachOnUnregisteredEntityDoesNotThrow(): void
    {
        $entity = $this->makeEntity(99);
        $this->expectNotToPerformAssertions();
        $this->map->detach($entity); // must not throw
    }

    public function testClearEmptiesTheEntireMap(): void
    {
        $a = $this->makeEntity(1);
        $b = $this->makeEntity(2);
        $this->map->getOrRegister($a);
        $this->map->getOrRegister($b);

        $this->map->clear();

        $this->assertNull($this->map->get(StubEntity::class, 1));
        $this->assertNull($this->map->get(StubEntity::class, 2));
    }
}

class StubEntity implements EntityInterface
{
    private ?int $id = null;
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): static { $this->id = $id; return $this; }
    public static function getRepository(): ?string { return null; }
}

class OtherStubEntity implements EntityInterface
{
    private ?int $id = null;
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): static { $this->id = $id; return $this; }
    public static function getRepository(): ?string { return null; }
}
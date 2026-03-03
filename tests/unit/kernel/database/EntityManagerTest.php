<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Management\IdentityMap;
use App\Kernel\Connector\Management\EntityManager;
use App\Kernel\Connector\Interfaces\EntityInterface;

class EntityManagerTest extends TestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        $this->em = new EntityManager(new IdentityMap());
    }

    public function testPersistQueuesNewEntityWhenIdIsNull(): void
    {
        $entity = $this->makeEntity(null);
        $this->em->persist($entity);

        $this->assertTrue($this->em->isNew($entity));
        $this->assertTrue($this->em->isManaged($entity));
    }

    public function testPersistQueuesDirtyEntityWhenIdIsSet(): void
    {
        $entity = $this->makeEntity(1);
        $this->em->persist($entity);

        $this->assertFalse($this->em->isNew($entity));
        $this->assertTrue($this->em->isManaged($entity));
    }

    public function testPersistIsIdempotent(): void
    {
        $entity = $this->makeEntity(null);
        $this->em->persist($entity);
        $this->em->persist($entity);

        // Still managed, not duplicated — verified indirectly via detach
        $this->em->detach($entity);
        $this->assertFalse($this->em->isManaged($entity));
    }

    public function testPersistNewEntityRegistersInIdentityMapAfterIdIsSet(): void
    {
        $entity = $this->makeEntity(null);
        $this->em->persist($entity);

        // Simulate what flush() does after insert: entity receives its id
        $entity->setId(99);
        $this->em->getIdentityMap()->getOrRegister($entity);

        $found = $this->em->getIdentityMap()->get(EMUnitStub::class, 99);
        $this->assertSame($entity, $found);
    }

    public function testRemoveDequeuesFromPersist(): void
    {
        $entity = $this->makeEntity(5);
        $this->em->persist($entity);
        $this->em->remove($entity);

        $this->assertFalse($this->em->isManaged($entity));
    }

    public function testRemoveIsIdempotent(): void
    {
        $entity = $this->makeEntity(5);
        $this->em->remove($entity);
        $this->em->remove($entity);

        // Must not throw or cause any issue
        $this->assertFalse($this->em->isManaged($entity));
    }

    public function testRemoveAfterPersistNewEntityDequeuesIt(): void
    {
        $entity = $this->makeEntity(null);
        $this->em->persist($entity);

        $this->assertTrue($this->em->isNew($entity));

        $this->em->remove($entity);

        $this->assertFalse($this->em->isNew($entity));
        $this->assertFalse($this->em->isManaged($entity));
    }

    public function testDetachStopsTrackingNewEntity(): void
    {
        $entity = $this->makeEntity(null);
        $this->em->persist($entity);
        $this->em->detach($entity);

        $this->assertFalse($this->em->isManaged($entity));
    }

    public function testDetachRemovesFromIdentityMap(): void
    {
        $entity = $this->makeEntity(3);
        $this->em->persist($entity);
        $this->em->detach($entity);

        $this->assertNull($this->em->getIdentityMap()->get(EMUnitStub::class, 3));
    }

    public function testDetachOnUntrackedEntityDoesNotThrow(): void
    {
        $entity = $this->makeEntity(77);
        $this->expectNotToPerformAssertions();
        $this->em->detach($entity);
    }

    public function testClearResetsAllQueues(): void
    {
        $a = $this->makeEntity(null);
        $b = $this->makeEntity(2);

        $this->em->persist($a);
        $this->em->persist($b);
        $this->em->clear();

        $this->assertFalse($this->em->isManaged($a));
        $this->assertFalse($this->em->isManaged($b));
    }

    public function testClearResetsIdentityMap(): void
    {
        $entity = $this->makeEntity(2);
        $this->em->persist($entity);
        $this->em->clear();

        $this->assertNull($this->em->getIdentityMap()->get(EMUnitStub::class, 2));
    }

    public function testGetIdentityMapReturnsTheSameInstance(): void
    {
        $map = $this->em->getIdentityMap();
        $this->assertSame($map, $this->em->getIdentityMap());
    }

    public function testPersistDirtyEntityRegistersInIdentityMap(): void
    {
        $entity = $this->makeEntity(10);
        $this->em->persist($entity);

        $this->assertSame($entity, $this->em->getIdentityMap()->get(EMUnitStub::class, 10));
    }

    private function makeEntity(?int $id): EMUnitStub
    {
        $e = new EMUnitStub();
        $e->setId($id);
        return $e;
    }

    
}
class EMUnitStub implements EntityInterface
{
    private ?int $id = null;
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): static { $this->id = $id; return $this; }
    public static function getRepository(): ?string { return null; }
}
<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Datas\LazyBag;
use App\Kernel\Connector\AbstractEntity;
use PHPUnit\Framework\MockObject\MockObject;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Connector\Attributes\ManyToMany;
use App\Kernel\Connector\Management\IdentityMap;
use App\Kernel\Connector\Management\EntityManager;
use App\Kernel\Connector\Interfaces\ConnectorInterface;

class EntityManagerManyToManyTest extends TestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
        EntityManager::resetInstance();
        ConnectorDispatcher::resetConnector();
        parent::tearDown();
    }

    public function testFlushSkipsPivotSyncWhenBagNotDirty(): void
    {
        $connector = $this->createMock(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $this->em = EntityManager::getInstance(new IdentityMap());
        $connector->expects($this->once()) 
            ->method('executeQuery')
            ->willReturn(5);
 
        $connector->method('fetchQuery')->willReturn([]);
 
        $entity = new EMM2MOwnerStub();
        
        $this->em->persist($entity);
        $this->em->flush();
    }

    public function testFlushSyncsPivotWhenBagIsDirty(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $this->em = EntityManager::getInstance(new IdentityMap());
        $calls = [];
        $connector->method('executeQuery')
            ->willReturnCallback(function (string $sql, array $params) use (&$calls) {
                $calls[] = $sql;
                if (str_contains($sql, 'INSERT INTO') && !str_contains($sql, 'pivot')) {
                    return 7; // id de l'entité insérée
                }
                return true;
            });
 
        $target = new EMM2MTargetStub();
        $target->setId(42);
 
        $entity = new EMM2MOwnerStub();
        $entity->addTarget($target); // → bag devient dirty
 
        $this->em->persist($entity);
        $this->em->flush();
 
        $pivotCalls = array_filter($calls, fn($sql) => str_contains($sql, 'owner_stub_target_stubs'));
        $this->assertNotEmpty($pivotCalls);
    }

    public function testFlushSkipsInverseSide(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $this->em = EntityManager::getInstance(new IdentityMap());
        $calls = [];
        $connector->method('executeQuery')
            ->willReturnCallback(function (string $sql) use (&$calls) {
                $calls[] = $sql;
                return true;
            });
 
        $entity = new EMM2MInverseStub();
        $entity->setId(1);
 
        $target = new EMM2MOwnerStub();
        $target->setId(99);
        $entity->addOwner($target); // bag dirty mais côté inverse
 
        $this->em->persist($entity);
        $this->em->flush();
 
        $pivotCalls = array_filter($calls, fn($sql) => str_contains($sql, 'DELETE') || str_contains($sql, 'pivot'));
        $this->assertEmpty($pivotCalls);
    }

    public function testFlushSyncsCorrectIdsInPivot(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $this->em = EntityManager::getInstance(new IdentityMap());
        $insertParams = [];
        $connector->method('executeQuery')
            ->willReturnCallback(function (string $sql, array $params) use (&$insertParams) {
                if (str_contains($sql, 'INSERT INTO owner_stub_target_stubs')) {
                    $insertParams[] = $params;
                }
                return true;
            });
 
        $target1 = new EMM2MTargetStub();
        $target1->setId(10);
        $target2 = new EMM2MTargetStub();
        $target2->setId(20);
 
        $entity = new EMM2MOwnerStub();
        $entity->setId(5);
        $entity->addTarget($target1);
        $entity->addTarget($target2);
 
        $this->em->persist($entity);
        $this->em->flush();
 
        $this->assertCount(2, $insertParams);
        $targets = array_column($insertParams, ':target');
        $this->assertContains(10, $targets);
        $this->assertContains(20, $targets);
    }

    public function testFlushDeletesPreviousPivotRowsBeforeInserting(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $this->em = EntityManager::getInstance(new IdentityMap());
        $deleteCalled = false;
        $connector->method('executeQuery')
            ->willReturnCallback(function (string $sql, array $params) use (&$deleteCalled) {
                if (str_contains($sql, 'DELETE FROM owner_stub_target_stubs')) {
                    $deleteCalled = true;
                }
                return true;
            });
 
        $target = new EMM2MTargetStub();
        $target->setId(10);
 
        $entity = new EMM2MOwnerStub();
        $entity->setId(5);
        $entity->addTarget($target);
 
        $this->em->persist($entity);
        $this->em->flush();
 
        $this->assertTrue($deleteCalled);
    }

    public function testFlushRollsBackOnPivotSyncFailure(): void
    {
        $connector = $this->createMock(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $this->em = EntityManager::getInstance(new IdentityMap());
        $connector->method('executeQuery')
            ->willThrowException(new \Exception('DB error'));
        $connector->expects($this->once())
            ->method('rollBack');
 
        $target = new EMM2MTargetStub();
        $target->setId(10);
 
        $entity = new EMM2MOwnerStub();
        $entity->setId(5);
        $entity->addTarget($target);
 
        $this->em->persist($entity);
 
        $this->expectException(\App\Kernel\Connector\DatabaseException::class);
        $this->em->flush();
    }

    public function testFlushSyncsEmptyBagDeletesAllPivotRows(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $this->em = EntityManager::getInstance(new IdentityMap());
        $deleteSql = null;
        $connector->method('executeQuery')
            ->willReturnCallback(function (string $sql, array $params) use (&$deleteSql) {
                if (str_contains($sql, 'DELETE')) {
                    $deleteSql = $sql;
                }
                return true;
            });
 
        $entity = new EMM2MOwnerStub();
        $entity->setId(5);
        // On force le bag à être dirty sans ajouter d'éléments
        $entity->getTargets()->remove(new EMM2MTargetStub()); // déclenche dirty via remove()
 
        $this->em->persist($entity);
        $this->em->flush();
 
        $this->assertNotNull($deleteSql);
        /**@var string $deleteSql */
        $this->assertStringContainsString('owner_stub_target_stubs', $deleteSql);
    }
}

class EMM2MTargetStub extends AbstractEntity
{
    protected static ?string $repo = EMM2MTargetStubRepository::class;
}
 
class EMM2MOwnerStub extends AbstractEntity
{
    protected static ?string $repo = EMM2MOwnerStubRepository::class;
 
    #[ManyToMany(
        targetEntity: EMM2MTargetStub::class,
        ownerColumn: 'owner_stub_id',
        targetColumn: 'target_stub_id',
        inversedBy: 'owners',
        pivotTable: 'owner_stub_target_stubs'
    )]
    protected LazyBag $targets;
 
    public function __construct()
    {
        parent::__construct();
        $this->targets = new LazyBag(fn() => []);
    }
 
    public function getTargets(): LazyBag { return $this->targets; }
    public function setTargets(LazyBag $targets): self { $this->targets = $targets; return $this; }
 
    public function addTarget(EMM2MTargetStub $target): self
    {
        $this->addToManyToMany('targets', $target);
        return $this;
    }
}
 
class EMM2MInverseStub extends AbstractEntity
{
    protected static ?string $repo = EMM2MInverseStubRepository::class;
 
    #[ManyToMany(
        targetEntity: EMM2MOwnerStub::class,
        ownerColumn: 'target_stub_id',
        targetColumn: 'owner_stub_id',
        mappedBy: 'targets',
        pivotTable: 'owner_stub_target_stubs'
    )]
    protected LazyBag $owners;
 
    public function __construct()
    {
        parent::__construct();
        $this->owners = new LazyBag(fn() => []);
    }
 
    public function getOwners(): LazyBag { return $this->owners; }
    public function setOwners(LazyBag $owners): self { $this->owners = $owners; return $this; }
 
    public function addOwner(EMM2MOwnerStub $owner): self
    {
        $this->owners->addWithoutInitializing($owner);
        return $this;
    }
}
 
// Repositories minimalistes pour satisfaire getRepository() et getTableName()
class EMM2MTargetStubRepository extends \App\Kernel\Connector\AbstractRepository
{
    protected ?string $entity = EMM2MTargetStub::class;
}
 
class EMM2MOwnerStubRepository extends \App\Kernel\Connector\AbstractRepository
{
    protected ?string $entity = EMM2MOwnerStub::class;
 
    public function save(\App\Kernel\Connector\Interfaces\EntityInterface $entity): null|false|\App\Kernel\Connector\Interfaces\EntityInterface
    {
        if (null === $entity->getId()) {
            $id = $this->connector->executeQuery('INSERT', []);
            $entity->setId((int) $id);
        }
        return $entity;
    }
}
 
class EMM2MInverseStubRepository extends \App\Kernel\Connector\AbstractRepository
{
    protected ?string $entity = EMM2MInverseStub::class;
 
    public function save(\App\Kernel\Connector\Interfaces\EntityInterface $entity): null|false|\App\Kernel\Connector\Interfaces\EntityInterface
    {
        return $entity;
    }
}
<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Psr14\Events\PostFindEvent;
use App\Kernel\Psr14\Events\PreRemoveEvent;

use App\Kernel\Psr14\Events\PreUpdateEvent;
use App\Kernel\Connector\AbstractRepository;
use App\Kernel\Psr14\Events\PostRemoveEvent;
use App\Kernel\Psr14\Events\PostUpdateEvent;
use App\Kernel\Psr14\Events\PrePersistEvent;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Psr14\Events\PostPersistEvent;
use App\Kernel\Psr14\Listener\ListenerProvider;
use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;

include_once 'EntityToCreate.php';
include_once 'EntityToUpdate.php';

class AbstractRepositoryEventTest extends TestCase
{
    private function makeListener(callable $callback): ListenerInterface
    {
        return new class($callback) implements ListenerInterface {
            public function __construct(private $callback) {}
            public function execute(StoppableEventInterface $event): void
            {
                ($this->callback)($event);
            }
        };
    }

    private function makeRepository(string $entity): AbstractRepository
    {
        return new class($entity) extends AbstractRepository {
            public function __construct(private string $entityClass)
            {
                $this->entity = $entityClass;
                parent::__construct();
            }
        };
    }

    protected function setUp(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
    }

    protected function tearDown(): void
    {
        ListenerProvider::getInstance()->resetListeners();
    }

    public function testPrePersistEventIsDispatched(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')->willReturn(20);
        ConnectorDispatcher::setConnector($connector);
        $repository = $this->makeRepository(EntityToCreate::class);
        $called = false;

        ListenerProvider::getInstance()->addListener(
            PrePersistEvent::class,
            $this->makeListener(function (PrePersistEvent $event) use (&$called) {
                $called = true;
            })
        );

        $entity = new EntityToCreate();
        $entity->setName('Doe')
            ->setFirstName('John');
        $repository->save($entity);
        $this->assertTrue($called);
    }

    public function testPrePersistEventCanModifyEntity(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')->willReturn(20);
        ConnectorDispatcher::setConnector($connector);
        $repository = $this->makeRepository(EntityToCreate::class);

        ListenerProvider::getInstance()->addListener(
            PrePersistEvent::class,
            $this->makeListener(function (PrePersistEvent $event) {
                $entity = $event->getEntity();
                /**@var EntityToCreate $entity */
                $entity->setName('Modified');
            })
        );

        $entity = new EntityToCreate();
        $entity->setName('Doe')->setFirstName('John');
        /**@var EntityToCreate $result */
        $result = $repository->save($entity);
        $this->assertEquals('Modified', $result->getName());
    }

    public function testPostPersistEventIsDispatched(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')->willReturn(20);
        ConnectorDispatcher::setConnector($connector);

        $repository = $this->makeRepository(EntityToCreate::class);
        $called = false;

        ListenerProvider::getInstance()->addListener(
            PostPersistEvent::class,
            $this->makeListener(function () use (&$called) {
                $called = true;
            })
        );
        $entity = new EntityToCreate();
        $entity->setName('Doe')->setFirstName('John');
        $repository->save($entity);
        $this->assertTrue($called);
    }

    public function testPostPersistEventEntityHasId(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')->willReturn(20);
        ConnectorDispatcher::setConnector($connector);

        $repository = $this->makeRepository(EntityToCreate::class);
        $idAtDispatchTime = null;

        ListenerProvider::getInstance()->addListener(
            PostPersistEvent::class,
            $this->makeListener(function (PostPersistEvent $event) use (&$idAtDispatchTime) {
                $idAtDispatchTime = $event->getEntity()->getId();
            })
        );

        $entity = new EntityToCreate();
        $entity->setName('Doe')->setFirstName('John');
        $repository->save($entity);
        $this->assertEquals(20, $idAtDispatchTime); 
    }

    public function testPostPersistEventNotDispatchedOnFailure(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')->willReturn(false);
        ConnectorDispatcher::setConnector($connector);

        $repository = $this->makeRepository(EntityToCreate::class);
        $called = false;

        ListenerProvider::getInstance()->addListener(
            PostPersistEvent::class,
            $this->makeListener(function () use (&$called) {
                $called = true;
            })
        );

        $entity = new EntityToCreate();
        $entity->setName('Doe')->setFirstName('John');
        $repository->save($entity);
        $this->assertFalse($called);
    }

      public function testPreUpdateEventIsDispatched(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')->willReturn(true);
        ConnectorDispatcher::setConnector($connector);

        $repository = $this->makeRepository(EntityToUpdate::class);
        $called = false;

        ListenerProvider::getInstance()->addListener(
            PreUpdateEvent::class,
            $this->makeListener(function () use (&$called) {
                $called = true;
            })
        );

        $entity = new EntityToUpdate();
        $entity->setName('Doe')->setId(2)->setFirstName('John');
        $repository->save($entity);
        $this->assertTrue($called);
    }

    public function testPreUpdateEventCanModifyEntity(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')->willReturn(true);
        ConnectorDispatcher::setConnector($connector);

        $repository = $this->makeRepository(EntityToUpdate::class);

        ListenerProvider::getInstance()->addListener(
            PreUpdateEvent::class,
            $this->makeListener(function (PreUpdateEvent $event) {
                $entity = $event->getEntity();
                /**@var EntityToUpdate $entity */
                $entity->setName('Modified');
            })
        );

        $entity = new EntityToUpdate();
        $entity->setName('Doe')->setId(2)->setFirstName('John');
        /**@var EntityToUpdate $result */
        $result = $repository->save($entity);
        $this->assertEquals('Modified', $result->getName());
    }

    public function testPostUpdateEventIsDispatched(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')->willReturn(true);
        ConnectorDispatcher::setConnector($connector);

        $repository = $this->makeRepository(EntityToUpdate::class);
        $called = false;

        ListenerProvider::getInstance()->addListener(
            PostUpdateEvent::class,
            $this->makeListener(function () use (&$called) {
                $called = true;
            })
        );

        $entity = new EntityToUpdate();
        $entity->setName('Doe')->setId(2)->setFirstName('John');
        $repository->save($entity);

        $this->assertTrue($called);
    }

    public function testPostUpdateEventNotDispatchedOnFailure(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')->willReturn(false);
        ConnectorDispatcher::setConnector($connector);

        $repository = $this->makeRepository(EntityToUpdate::class);
        $called = false;

        ListenerProvider::getInstance()->addListener(
            PostUpdateEvent::class,
            $this->makeListener(function () use (&$called) {
                $called = true;
            })
        );

        $entity = new EntityToUpdate();
        $entity->setName('Doe')->setId(2)->setFirstName('John');
        $repository->save($entity);

        $this->assertFalse($called);
    }

    public function testPreRemoveEventIsDispatched(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')->willReturn(true);
        ConnectorDispatcher::setConnector($connector);

        $repository = $this->makeRepository(EntityToCreate::class);
        $called = false;

        ListenerProvider::getInstance()->addListener(
            PreRemoveEvent::class,
            $this->makeListener(function () use (&$called) {
                $called = true;
            })
        );

        $entity = new EntityToCreate();
        $entity->setName('Doe')->setId(1)->setFirstName('John');
        $repository->delete($entity);
        $this->assertTrue($called);
    }

    public function testPostRemoveEventIsDispatched(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')->willReturn(true);
        ConnectorDispatcher::setConnector($connector);

        $repository = $this->makeRepository(EntityToCreate::class);
        $called = false;

        ListenerProvider::getInstance()->addListener(
            PostRemoveEvent::class,
            $this->makeListener(function () use (&$called) {
                $called = true;
            })
        );

        $entity = new EntityToCreate();
        $entity->setName('Doe')->setId(1)->setFirstName('John');
        $repository->delete($entity);
        $this->assertTrue($called);
    }

    public function testPostRemoveEventEntityDataStillAccessible(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')->willReturn(true);
        ConnectorDispatcher::setConnector($connector);

        $repository = $this->makeRepository(EntityToCreate::class);
        $nameAtDispatchTime = null;

        ListenerProvider::getInstance()->addListener(
            PostRemoveEvent::class,
            $this->makeListener(function (PostRemoveEvent $event) use (&$nameAtDispatchTime) {
                /**@var EntityToCreate $entity */
                $entity = $event->getEntity();
                $nameAtDispatchTime = $entity->getName();
            })
        );

        $entity = new EntityToCreate();
        $entity->setName('Doe')->setId(1)->setFirstName('John');
        $repository->delete($entity);
        $this->assertEquals('Doe', $nameAtDispatchTime); 
    }

    public function testPostRemoveEventNotDispatchedOnFailure(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')->willReturn(false);
        ConnectorDispatcher::setConnector($connector);

        $repository = $this->makeRepository(EntityToCreate::class);
        $called = false;

        ListenerProvider::getInstance()->addListener(
            PostRemoveEvent::class,
            $this->makeListener(function () use (&$called) {
                $called = true;
            })
        );

        $entity = new EntityToCreate();
        $entity->setName('Doe')->setId(1)->setFirstName('John');
        $repository->delete($entity);
        $this->assertFalse($called);
    }

    public function testPostFindEventIsDispatchedOnFind(): void
    {
        $values = [[
            'id' => 1,
            'name' => 'Doe',
            'first_name' => 'John',
            'age' => 30
        ]];

        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')->willReturn($values);
        ConnectorDispatcher::setConnector($connector);

        $repository = $this->makeRepository(EntityToCreate::class);
        $called = false;

        ListenerProvider::getInstance()->addListener(
            PostFindEvent::class,
            $this->makeListener(function () use (&$called) {
                $called = true;
            })
        );

        $repository->find(1);
        $this->assertTrue($called);
    }

    public function testPostFindEventIsDispatchedOncePerEntityOnFindAll(): void
    {
        $values = [
            ['id' => 1, 'name' => 'Doe', 'first_name' => 'John', 'age' => 30],
            ['id' => 2, 'name' => 'Killin', 'first_name' => 'Jane', 'age' => 25],
        ];

        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')->willReturn($values);
        ConnectorDispatcher::setConnector($connector);

        $repository = $this->makeRepository(EntityToCreate::class);
        $callCount = 0;

        ListenerProvider::getInstance()->addListener(
            PostFindEvent::class,
            $this->makeListener(function () use (&$callCount) {
                $callCount++;
            })
        );

        $repository->findAll();
        $this->assertEquals(2, $callCount); 
    }

    public function testPostFindEventIsDispatchedOncePerEntityOnFindBy(): void
    {
        $values = [
            ['id' => 1, 'name' => 'Doe', 'first_name' => 'John', 'age' => 30],
            ['id' => 2, 'name' => 'Doe', 'first_name' => 'Jane', 'age' => 25],
        ];

        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')->willReturn($values);
        ConnectorDispatcher::setConnector($connector);

        $repository = $this->makeRepository(EntityToCreate::class);
        $callCount = 0;

        ListenerProvider::getInstance()->addListener(
            PostFindEvent::class,
            $this->makeListener(function () use (&$callCount) {
                $callCount++;
            })
        );

        $repository->findBy(['name' => 'Doe']);
        $this->assertEquals(2, $callCount);
    }

    public function testPostFindEventEntityIsFullyHydrated(): void
    {
        $values = [[
            'id' => 1,
            'name' => 'Doe',
            'first_name' => 'John',
            'age' => 30
        ]];

        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')->willReturn($values);
        ConnectorDispatcher::setConnector($connector);

        $repository = $this->makeRepository(EntityToCreate::class);
        /**@var EntityToCreate $entityAtDispatchTime */
        $entityAtDispatchTime = null;

        ListenerProvider::getInstance()->addListener(
            PostFindEvent::class,
            $this->makeListener(function (PostFindEvent $event) use (&$entityAtDispatchTime) {
                $entityAtDispatchTime = $event->getEntity();
            })
        );

        $repository->find(1);
        $this->assertInstanceOf(EntityToCreate::class, $entityAtDispatchTime);
        $this->assertEquals(1, $entityAtDispatchTime->getId());
        $this->assertEquals('Doe', $entityAtDispatchTime->getName());
        $this->assertEquals('John', $entityAtDispatchTime->getFirstName());
        $this->assertEquals(30, $entityAtDispatchTime->getAge());
    }

    public function testListenerCanUseBagToPassDataBetweenListeners(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')->willReturn(20);
        ConnectorDispatcher::setConnector($connector);

        $repository = $this->makeRepository(EntityToCreate::class);

        ListenerProvider::getInstance()->addListener(
            PrePersistEvent::class,
            $this->makeListener(function (PrePersistEvent $event) {
                $event->addInBag('processed', true);
            })
        );

        $secondListenerSawFlag = false;
        ListenerProvider::getInstance()->addListener(
            PrePersistEvent::class,
            $this->makeListener(function (PrePersistEvent $event) use (&$secondListenerSawFlag) {
                $secondListenerSawFlag = $event->getFromBag('processed', false);
            })
        );

        $entity = new EntityToCreate();
        $entity->setName('Doe')->setFirstName('John');
        $repository->save($entity);
        $this->assertTrue($secondListenerSawFlag);
    }
}

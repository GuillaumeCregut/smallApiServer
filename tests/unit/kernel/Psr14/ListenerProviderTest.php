<?php

use PHPUnit\Framework\TestCase;
use APP\Kernel\Psr14\Exceptions\EventException;
use App\Kernel\Psr14\Listener\ListenerProvider;
use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;

class ListenerProviderTest extends TestCase
{

    public function testProviderInstance(): void
    {
        $provider = ListenerProvider::getInstance();
        $this->assertInstanceOf(ListenerProvider::class, $provider);
    }

    public function testProviderAddBadEvent(): void
    {
        // $event = $this->createStub(StoppableEventInterface::class);
        $event = $this->createStub(stdClass::class);
        $listener = $this->createStub(ListenerInterface::class);
        $provider = new ListenerProvider();
        $this->expectException(EventException::class);
        $provider->addListener($event::class, $listener);
    }

    public function testProviderAddEvent()
    {
        $event = $this->createStub(StoppableEventInterface::class);
        $listener = $this->createStub(ListenerInterface::class);
        $provider = new ListenerProvider();
        $provider->addListener($event::class, $listener);
        $this->assertArrayHasKey($event::class, $provider->getListeners());
        $this->assertIsArray($provider->getListeners($event::class));
    }

    public function testProviderUnknwonClass(): void
    {
        $listener1 = $this->createStub(ListenerInterface::class);
        $provider = new ListenerProvider();
        $this->expectException(EventException::class);
        $provider->addListener('some::class', $listener1, 3);
    }

    public function testRemoveEventFromProvider(): void
    {
        $event = $this->createStub(StoppableEventInterface::class);
        $listener = $this->createStub(ListenerInterface::class);
        $provider = new ListenerProvider();
        $provider->addListener($event::class, $listener);
        // $this->assertArrayHasKey($event::class, $provider->getListeners());
        // $this->assertIsArray($provider->getListeners($event::class));
        $provider->clearListener($event::class);
        $this->assertArrayNotHasKey($event::class, $provider->getListeners());
    }

    public function testProviderAddEventInstance()
    {
        $event = $this->createStub(StoppableEventInterface::class);
        $event2 = $this->createStub(StoppableEventInterface::class);
        $listener = $this->createStub(ListenerInterface::class);
        $provider = new ListenerProvider();
        $provider->addListener($event::class, $listener);
        $this->assertArrayHasKey($event::class, $provider->getListeners());
        $this->assertIsArray($provider->getListeners());
        $provider2 = ListenerProvider::getInstance();
        $provider2->addListener($event2::class, $listener);
        $provider3 = ListenerProvider::getInstance();
        $this->assertArrayHasKey($event2::class, $provider3->getListeners());
        $this->assertArrayHasKey($event::class, $provider3->getListeners());
    }

    public function testProviderPriority(): void
    {
        $listener1 = $this->createStub(ListenerInterface::class);
        $listener2 = $this->createStub(ListenerInterface::class);
        $event = $this->createStub(StoppableEventInterface::class);
        $provider = new ListenerProvider();
        $provider->addListener($event::class, $listener2, 3);
        $provider->addListener($event::class, $listener1, 5);
        $arrayEvent = $provider->getListenersForEvent(new $event());
        $this->assertEquals([ $listener1,$listener2], $arrayEvent);
    }
}

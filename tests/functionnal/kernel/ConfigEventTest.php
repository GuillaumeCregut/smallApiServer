<?php

use App\Kernel\MakeListener;
use PHPUnit\Framework\TestCase;
use App\Kernel\Psr14\Listener\ListenerProvider;
use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;

class ConfigEventTest extends TestCase
{

    protected function setUp(): void
    {
        $provider = ListenerProvider::getInstance();
        $provider->resetListeners();
    }
    public function testLoadEvents(): void
    {
        $event1 = $this->createStub(StoppableEventInterface::class);
        $event2 = $this->createStub(StoppableEventInterface::class);
        $listener1 = $this->createStub(ListenerInterface::class);
        $listener2 = $this->createStub(ListenerInterface::class);
        $listener3 = $this->createStub(ListenerInterface::class);
        $events = [
            get_class($event1) => [
                $listener1,
                $listener2
            ],
            get_class($event2) => [
                $listener1,
                $listener3
            ],
        ];
        $provider = ListenerProvider::getInstance();
        $maker = MakeListener::applyListener($events);
        $this->assertArrayHasKey($event1::class, $provider->getListeners());
        $this->assertArrayHasKey($event2::class, $provider->getListeners());
        $listeners1 =  $provider->getListenersForEvent($event1);
        $listeners2 =  $provider->getListenersForEvent($event2);
        $this->assertEquals([ $listener1,$listener2], $listeners1);
        $this->assertEquals([ $listener1,$listener3], $listeners2);
        
    }
}

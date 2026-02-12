<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Psr14\Events\InitKernelEvent;
use App\Kernel\Psr14\Listener\ListenerProvider;
use App\Kernel\Psr14\Dispatcher\EventDispatcher;
use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;
use App\Kernel\Psr14\Exceptions\EventException;

class EventDispatcherTest extends TestCase
{
    public function testInstance(): void
    {
        $listenerProvider = ListenerProvider::getInstance();
        $dispatcher = EventDispatcher::getInstance($listenerProvider);
        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
    }

    public function testInitWithoutProvider(): void
    {
        EventDispatcher::resetInstance();
        $this->expectException(EventException::class);
         $dispatcher = EventDispatcher::getInstance();
    }

    public function testGetInstanceOK(): void
    {
        EventDispatcher::resetInstance();
        $listenerProvider = ListenerProvider::getInstance();
        $dispatcher = EventDispatcher::getInstance($listenerProvider); 
        $dispatcher2 = EventDispatcher::getInstance();
        $this->assertInstanceOf(EventDispatcher::class, $dispatcher2);
    }
    public function testDispatch(): void
    {
        $listener = $this->createMock(ListenerInterface::class);
        $provider=new ListenerProvider();
        $provider->addListener( InitKernelEvent::class, $listener, 3);
        $eventDispatcher= new EventDispatcher($provider);
        $listener->expects($this->once())
        ->method('execute');
        $eventDispatcher->dispatch(new InitKernelEvent());       
    }

    public function testBadEventThrown(): void
    {
        $listener = $this->createStub(ListenerInterface::class);
        $event = $this->createStub(stdClass::class);
        $provider=new ListenerProvider();
        $provider->addListener(InitKernelEvent::class, $listener, 3);
        $eventDispatcher= new EventDispatcher($provider);
        $this->expectException(EventException::class);
        $eventDispatcher->dispatch($event);   
    }

    public function testStopBlockEvent(): void
    {
        $listener = $this->createMock(ListenerInterface::class);
        $event = $event = $this->createStub(StoppableEventInterface::class);
        $event->method('isPropagationStopped')
            ->willReturn(true);
        $provider=new ListenerProvider();
        $provider->addListener(get_class($event), $listener, 3);
        $eventDispatcher= new EventDispatcher($provider);
        $listener->expects($this->exactly(0))
        ->method('execute');
        $eventDispatcher->dispatch($event);    
    }
} 
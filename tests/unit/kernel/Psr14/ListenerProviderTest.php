<?php
error_reporting(E_ALL);
ini_set('error_log', null);
ini_set('log_errors', false);

use PHPUnit\Framework\TestCase;
use APP\Kernel\Psr14\Exceptions\EventException;
use App\Kernel\Psr14\Listener\ListenerProvider;
use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;

class ListenerProviderTest extends TestCase
{
    protected function setUp(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    }
    public function testProviderInstance(): void
    {
        $provider= ListenerProvider::getInstance();
        $this->assertInstanceOf(ListenerProvider::class, $provider);
    }

    public function testProviderAddBadEvent(): void
    {
       // $event = $this->createStub(StoppableEventInterface::class);
       $event = $this->createStub(stdClass::class);
       $listener = $this->createStub(ListenerInterface::class);
        $provider=new ListenerProvider();
        $this->expectException(EventException::class);
        $provider->addListener($event::class, $listener );
    }
     public function testProviderAddEvent()
    {
        $event = $this->createStub(StoppableEventInterface::class);
        $listener = $this->createStub(ListenerInterface::class);
        $provider=new ListenerProvider();
        $provider->addListener($event::class, $listener );
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
        $provider=new ListenerProvider();
        $provider->addListener($event::class, $listener );
        // $this->assertArrayHasKey($event::class, $provider->getListeners());
        // $this->assertIsArray($provider->getListeners($event::class));
        $provider->clearListener($event::class);
        $this->assertArrayNotHasKey($event::class, $provider->getListeners());
    }
}
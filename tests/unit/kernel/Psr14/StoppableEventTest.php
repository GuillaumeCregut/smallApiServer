<?php

use App\Kernel\Psr14\Events\AbstractStoppableEvent;
use PHPUnit\Framework\TestCase;

class StoppableEventTest extends TestCase
{
    //test is we change stop
    public function testIsStopped(): void
    {
        $event = new myEvent();
        $event->stopPropagation();
        $this->assertTrue($event->isPropagationStopped());
    }
    public function testAddAndGetFromBag(): void
    {
        $event = new myEvent();
        $event->addInBag('myKey', 12);
        $this->assertSame(12, $event->getFromBag('myKey'));
    }
    public function testGetFromBagWithDefault(): void
    {
        $event = new myEvent();
        $this->assertSame(12, $event->getFromBag('myKey', 12));
    }

    public function testGetFromBagDefaultIsNull(): void
    {
        $event = new myEvent();
        $this->assertNull($event->getFromBag('key'));
    }

    public function testHasInBag(): void
    {
        $event = new myEvent();
        $event->addInBag('myKey', 12);
        $this->assertTrue($event->hasInBag('myKey'));
    }

    public function testHasInBagWithNullValue(): void
    {
        $event = new myEvent();
        $event->addInBag('myKey', null);
        $this->assertNull($event->getFromBag('myKey'));
    }

    public function testRemoveFromBag(): void
    {
        $event = new myEvent();
        $event->addInBag('myKey', 12);
        $event->removeFromBag('myKey');
        $this->assertFalse($event->hasInBag('myKey'));
    }

    public function testGetBag(): void
    {
        $event = new myEvent();
        $event->addInBag('myKey', 12);
        $expected = ['myKey' => 12];
        $this->assertSame($expected, $event->getBag());
    }

    public function testOverwriteBagKey(): void
    {
        $event = new myEvent();
        $event->addInBag('myKey', 12);
        $event->addInBag('myKey', 13);
        $expected = ['myKey' => 13];
        $this->assertSame($expected, $event->getBag());
    }
}

class myEvent extends AbstractStoppableEvent {}

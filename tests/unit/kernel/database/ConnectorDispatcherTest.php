<?php

use App\Interfaces\ConnectorInterface;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Connector\DatabaseException;
use PHPUnit\Framework\TestCase;

class ConnectorDispatcherTest extends TestCase
{
    public function testNoConnector():void
    {
        ConnectorDispatcher::resetConnector();
        $this->expectException(DatabaseException::class);
        $connector = ConnectorDispatcher::getConnector();
    }

    public function testOKConnector(): void
    {
        ConnectorDispatcher::resetConnector();
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $newConnector = ConnectorDispatcher::getConnector();
        $this->assertInstanceOf(ConnectorInterface::class, $newConnector);
    }
}
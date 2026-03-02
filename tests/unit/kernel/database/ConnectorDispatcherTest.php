<?php


use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\DatabaseException;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Connector\Interfaces\ConnectorInterface;

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
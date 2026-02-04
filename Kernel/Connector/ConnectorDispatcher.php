<?php

namespace App\Kernel\Connector;

use App\Kernel\Interfaces\Databases\ConnectorInterface;

class ConnectorDispatcher
{
    private static ?ConnectorInterface $connector=null;

    public static function setConnector(ConnectorInterface $connector): void
    {
        self::$connector = $connector;
    }

    public static function getConnector(): ConnectorInterface
    {
        if(null === self::$connector) {
            throw new DatabaseException('Connector not set');
        }
        return self::$connector;
    }

    public static function resetConnector(): void
    {
        self::$connector=null;
    }
}
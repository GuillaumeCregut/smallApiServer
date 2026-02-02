<?php

namespace App\Kernel\Config;

use App\Interfaces\ConnectorInterface;
use App\Kernel\Connector\MySQLConnector;

class DatabaseConnector
{
    public static function getConnector(): ConnectorInterface
    {
        //Here spécify default database connector class you use
        return MySQLConnector::getInstance();
    }

}
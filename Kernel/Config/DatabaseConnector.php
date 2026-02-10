<?php

namespace App\Kernel\Config;


use App\Kernel\Connector\MySQLConnector;
use App\Kernel\GetEnvDatas;
use App\Kernel\Interfaces\Databases\ConnectorInterface;

class DatabaseConnector
{
    public static function getConnector(): ConnectorInterface
    {
        //Here spécify default database connector class you use
        $env = GetEnvDatas::getEnvInstance()->getDdCredentials();
        return MySQLConnector::getInstance($env);
    }

}
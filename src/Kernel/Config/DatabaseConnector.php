<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Config;


use App\Kernel\Connector\MySQLConnector;
use App\Kernel\GetEnvDatas;
use App\Kernel\Connector\Interfaces\ConnectorInterface;

class DatabaseConnector
{
    public static function getConnector(): ConnectorInterface
    {
        //Here spécify default database connector class you use
        $env = GetEnvDatas::getEnvInstance()->getDdCredentials();
        return MySQLConnector::getInstance($env);
    }

    public static function getDetachedConnector(): ConnectorInterface
    {
        //Provide DB connexion without using configured database
        $env = GetEnvDatas::getEnvInstance()->getDdCredentials();
        return MySQLConnector::getDetachedConnector($env);
    }

}
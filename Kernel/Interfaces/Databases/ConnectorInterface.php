<?php

namespace App\Kernel\Interfaces\Databases;


interface ConnectorInterface 
{
    public function getConnection(): mixed;
    public static function getInstance(): ConnectorInterface;
}

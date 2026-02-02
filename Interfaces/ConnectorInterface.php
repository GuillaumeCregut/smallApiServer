<?php

namespace App\Interfaces;


interface ConnectorInterface 
{
    public function getConnection(): mixed;
    public static function getInstance(): ConnectorInterface;
}

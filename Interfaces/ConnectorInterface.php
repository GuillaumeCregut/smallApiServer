<?php

namespace App\Interfaces;


interface ConnectorInterface 
{
    public function getConnection(): mixed;
}

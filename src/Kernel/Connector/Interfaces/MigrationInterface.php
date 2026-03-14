<?php

namespace App\Kernel\Connector\Interfaces;

use App\Kernel\Connector\Interfaces\ConnectorInterface;

interface MigrationInterface
{
    public function up(ConnectorInterface $connector): void;
    public function down(ConnectorInterface $connector): void;
}
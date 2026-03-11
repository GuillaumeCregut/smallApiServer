<?php

namespace App\Listeners;

use App\Kernel\Config\DatabaseConnector;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;

class DbConnectionListener implements ListenerInterface
{

    public function execute(StoppableEventInterface $event): void
    {
        ConnectorDispatcher::setConnector(DatabaseConnector::getConnector());
        $event->stopPropagation();
    }
}

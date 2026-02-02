<?php

namespace App\Kernel\Psr14\Listener;

use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;

class TestListener implements ListenerInterface
{
    public function execute(StoppableEventInterface $event): void
    {
        
    }
}
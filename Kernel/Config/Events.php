<?php

namespace App\Kernel\Config;

use App\Kernel\Psr14\Events\InitKernelEvent;
use App\Kernel\Psr14\Listener\TestListener;

class Events
{
    /*
        event array like :
        [
            event::class =>[
                new EventListener(),
                new EventListener(),
            ],
            event::class =>[
                new EventListener(),
            ]
        ]
    Usabe KernelEvent :
    - InitKernelEvent
    - CallAuthKernelEvent
    - CheckApiKeyKernelEvent
    - ConnectorKernelEvent
    - ReturnResponseKernelEvent
    - StartControllerKernelEvent
    */
    public static function getListeners(): array
    {
        $events = [
            InitKernelEvent::class=>[new TestListener()]
        ];
        return $events;
    }
}
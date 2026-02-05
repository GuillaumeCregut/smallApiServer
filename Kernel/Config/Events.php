<?php

namespace App\Kernel\Config;

use App\Kernel\Middleware\Security\AuthManagerMiddleware;
use App\Kernel\Psr14\Events\CallAuthKernelEvent;

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
    Note : order is important in array. the last class is called first.
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
            CallAuthKernelEvent::class=>[new AuthManagerMiddleware()]
        ];
        return $events;
    }
}
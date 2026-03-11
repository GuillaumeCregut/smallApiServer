<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Config;

use App\Listeners\DbConnectionListener;
use App\Kernel\Psr14\Events\CallAuthKernelEvent;
use App\Kernel\Psr14\Events\ConnectorKernelEvent;
use App\Kernel\Middleware\Security\AuthManagerMiddleware;

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
            CallAuthKernelEvent::class => [new AuthManagerMiddleware()],
            ConnectorKernelEvent::class => [new DbConnectionListener()],
        ];
        return $events;
    }
}
<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Config;

use App\Kernel\Security\CsrfManager;
use App\Listeners\DbConnectionListener;
use App\Kernel\Psr14\Events\CheckCsrfEvent;
use App\Kernel\Psr14\Events\CallAuthKernelEvent;
use App\Kernel\Psr14\Events\ConnectorKernelEvent;
use App\Kernel\Psr14\Events\ReturnResponseKernelEvent;
use App\Kernel\Middleware\Security\AuthManagerMiddleware;
use App\Kernel\Middleware\Security\Csrf\CsrfValidationListener;
use App\Kernel\Middleware\Security\Csrf\CsrfTokenInjectorListener;

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
            CheckCsrfEvent::class =>[new CsrfValidationListener(new CsrfManager())],
            ReturnResponseKernelEvent::class =>[new CsrfTokenInjectorListener(new CsrfManager())]
        ];
        return $events;
    }
}
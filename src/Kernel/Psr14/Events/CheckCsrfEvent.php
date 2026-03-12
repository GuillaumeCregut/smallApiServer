<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Psr14\Events;

use App\Kernel\Request;

class CheckCsrfEvent extends AbstractStoppableEvent
{
    //Launched when kernel needs Csrf check
    public function __construct(
        public readonly Request $request
    ) {}
}

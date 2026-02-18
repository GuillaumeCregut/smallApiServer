<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Interfaces\Psr14;

interface ListenerInterface
{
     /**
     * @param mixed ...$args
     *
     * @return void
     */
    public function execute(StoppableEventInterface $event): void;
}
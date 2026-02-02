<?php

namespace App\Kernel\Interfaces\Psr14;

interface ListenerInterface
{
     /**
     * @param mixed ...$args
     *
     * @return void
     */
    public function execute(...$args): void;
}
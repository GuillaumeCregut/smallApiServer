<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel;

use App\Kernel\Psr14\Listener\ListenerProvider;

class MakeListener
{
    public static function applyListener(array $config):void
    {
        if(empty($config)) {
            return;
        }
        $provider = ListenerProvider::getInstance();
        foreach($config as $event=>$listener) {
            foreach($listener as $num=>$class) {
                $provider->addListener($event, $class, $num);
            }
        }
    }
}
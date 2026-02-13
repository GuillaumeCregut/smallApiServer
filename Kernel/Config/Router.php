<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Config;

use App\Controllers\HomeController;

class Router
{
    public static function getRoutes(): array
    {
        //Here are routes used. Add Your own one here
        //Format is : 'route' =>[Controller FQN or class, method ]
        //No starting "/" for route, and no trailing "/" too.
        return [
            '' => [HomeController::class, 'index',],
        ];
    }
}

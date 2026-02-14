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
        /*Here are routes used. Add Your own here
        Format is : 'route' =>['HTTP_METHOD'=>[Controller FQN or class, method ]
        example : homePage
        return [
            //homepage http://ipserver/ Only GET method, others will return 405
            '' =>[ 
                'GET'=>[HomeController::class, 'index'],
            //user page http;//iperserver/user.
            'user' =>[ 
                'GET'=>[UserController::class, 'index'],
                'POST'=>[UserController::class, 'add']
                'PUT'=>[UserController::class, 'modify']
                'DELETE'=>[UserController::class, 'delete']
                ],
        ]
        No starting "/" for route, and no trailing "/" too.
        */
        return [
            '' => [
                'GET' => [HomeController::class, 'getDatas'],
                'POST' => [HomeController::class,'addData'],
                'PUT' => [HomeController::class,'changeData'],
                'PATCH' => [HomeController::class,'changeData'],
                'DELETE' => [HomeController::class,'deleteData']
            ],
        ];
    }
}

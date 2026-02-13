<?php

namespace App\Kernel\Middleware\Security;

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

use App\Kernel\Request;
use App\Kernel\Interfaces\AuthenticationInterface;
use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;
use App\Security\UserRepository;

class AuthManagerMiddleware implements ListenerInterface
{
    /*Purose
    - Identify type of authentication and set the correct middleware*/
    public function execute(StoppableEventInterface $event): void
    {
        $auth = null;
        $connector = new UserRepository();
        $request = Request::getRequestInstance();
        if (!is_null($request->getHeaders('Authorization'))) {
            $auth = new AuthBearerMiddleware($connector);
        }
        if ($request->getSessionValue('userId') !== null) {
            $auth = new SessionAuthMiddleware($connector);
        }
        if ((null !== $request->getServer('PHP_AUTH_USER')) && (null !== $request->getServer('PHP_AUTH_PW'))) {
            $auth = new AuthHttpMiddleware($connector);
        }
        $request->setUser($auth->getUser());
        $event->stopPropagation();
    }
}

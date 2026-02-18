<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Middleware\Security;

use App\Kernel\Request;
use App\Security\UserRepositoryAuth;
use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;

class AuthManagerMiddleware implements ListenerInterface
{
    /*Purose
    - Identify type of authentication and set the correct middleware*/
    public function execute(StoppableEventInterface $event): void
    {
        $auth = null;
        $connector = new UserRepositoryAuth();
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
        if(null === $auth) {
            return;
        }
        $request->setUser($auth->getUser());
        $event->stopPropagation();
    }
}

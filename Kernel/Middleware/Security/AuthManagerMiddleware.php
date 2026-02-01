<?php

namespace App\Kernel\Middleware\Security;

use App\Interfaces\ConnectorInterface;
use App\Kernel\Interfaces\AuthenticationInterface;
use App\Kernel\Request;

class AuthManagerMiddleware
{
    /*Role de la classe
    -Identifier le type d'authentification et délivre le bon middleware*/

    public static function getAuthMiddleware(ConnectorInterface $connector): ?AuthenticationInterface
    {
        $request = Request::getRequestInstance();
        //Check auth
        if (!is_null($request->getHeaders('Authorization'))) {
            //Bearer Auth 
            return new AuthBearerMiddleware($connector);
        }
        if ($request->getSessionValue('userId') !== null) {
            //Session Auth
            return new SessionAuthMiddleware($connector);
        }
        if ((null !== $request->getServer('PHP_AUTH_USER')) && (null !== $request->getServer('PHP_AUTH_PW'))) {
            //HTTP Auth
            return new HttpAuthMiddleware($connector);
        }
        return null;
    }
}

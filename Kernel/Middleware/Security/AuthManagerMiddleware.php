<?php

namespace App\Kernel\Middleware\Security;

use App\Interfaces\ConnectorInterface;
use App\Kernel\Config\DatabaseConnector;
use App\Kernel\Interfaces\AuthenticationInterface;
use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;
use App\Kernel\Request;

class AuthManagerMiddleware implements ListenerInterface
{
    /*Purose
    - Identify type of authentication and set the correct middleware*/
    public function execute(StoppableEventInterface $event): void
    {
        $auth = null;
        $connector = DatabaseConnector::getConnector();
         $request = Request::getRequestInstance();
        if (!is_null($request->getHeaders('Authorization'))) { 
            $auth=new AuthBearerMiddleware($connector);
        }
        if ($request->getSessionValue('userId') !== null) {
            $auth = new SessionAuthMiddleware($connector);
        }
        if ((null !== $request->getServer('PHP_AUTH_USER')) && (null !== $request->getServer('PHP_AUTH_PW'))) {
            $auth = new AuthHttpMiddleware($connector);
        }
        $request->setAuth($auth);
        $event->stopPropagation();
    }
    #[\Deprecated(message: "use execute() instead", since: "0.2")]
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
            return new AuthHttpMiddleware($connector);
        }
        return null;
    }
}

<?php

namespace App\Middleware;

use App\Interfaces\ConnectorInterface;
use App\Kernel\Interfaces\AuthenticationInterface;
use App\Kernel\Request;

class AuthManagerMiddleware
{
    /*Role de la classe
    -Identifier le type d'authentification et délivre le bon middleware*/
    public function __construct(private ConnectorInterface $connector)
    {
        
    }

    public function getAuthMiddleware(): ?AuthenticationInterface
    {
        $request = Request::getRequestInstance();
        //Check auth
        if((null!==$request->getServer('PHP_AUTH_USER')) && (null!==$request->getServer('PHP_AUTH_PW'))){
            //HTTP Auth
            return new HttpAuthMiddleware($this->connector);
        } 
        if(!is_null($request->getHeaders('Authorization'))){
            //Bearer Auth 
            //Todo : complete
            return new AuthBearerMiddleware($this->connector);
        }
        if($request->getSessionValue('user_id') !== null){
            //Session Auth
            return new SessionAuthMiddleware($this->connector);
        }
        return null;
    }

}
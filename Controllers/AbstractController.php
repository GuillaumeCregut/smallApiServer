<?php

namespace App\Controllers;

use App\Services\RequestObject;
use App\Interfaces\ResponseInterface;
use App\Services\ClientErrorResponse;
use App\Interfaces\ConnectorInterface;
use App\Middleware\AuthBearerMiddleware;
use App\Interfaces\AuthenticationInterface;

abstract class AbstractController
{
    protected ConnectorInterface $connector;
   
    public function __construct(protected RequestObject $request, protected AuthenticationInterface $authMiddleware)
    {
        
    }

    protected function returnError(int $error): ResponseInterface
    {
        $response = new ClientErrorResponse($error);
                return $response;
    }

    protected function isUserAuth(): bool
    {
        $requestAuth = $this->request->getAuthUser();
        if($requestAuth === null){
            return false;
        }
        //Todo : make authentication
        //Pour le moment, on ne gère que l'auth Bearer
        if($requestAuth[0] != 'Bearer'){
            return false;
        }
        $middleware = new AuthBearerMiddleware();
        return $middleware->isAuth($requestAuth[1]);
    }

} 
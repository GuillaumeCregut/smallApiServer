<?php

namespace App\Kernel;

use App\Kernel\RequestObject;
use App\Kernel\Interfaces\ResponseInterface;
use App\Services\Responses\ClientErrorResponse;
use App\Interfaces\ConnectorInterface;
use App\Middleware\AuthBearerMiddleware;
use App\Interfaces\AuthenticationInterface;

abstract class AbstractController
{
    protected ConnectorInterface $connector;
    protected RequestObject $request;
    public function __construct(protected AuthenticationInterface $authMiddleware)
    {
        $this->request = RequestObject::getRequestInstance();
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
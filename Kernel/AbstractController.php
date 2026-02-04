<?php

namespace App\Kernel;

use App\Kernel\Request;

use App\Kernel\Interfaces\ResponseInterface;
use App\Kernel\Responses\ClientErrorResponse;
use App\Kernel\Interfaces\Databases\ConnectorInterface;


abstract class AbstractController
{
    protected ConnectorInterface $connector;
    protected Request $request;

    public function __construct()
    {
        $this->request = Request::getRequestInstance();
    }

    protected function returnError(int $error): ResponseInterface
    {
        $response = new ClientErrorResponse($error);
                return $response;
    }

    protected function isUserAuth(): bool
    {
        return $this->request->isAuth();
    }

} 
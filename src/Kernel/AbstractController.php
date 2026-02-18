<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel;

use App\Kernel\Request;

use App\Kernel\Interfaces\ResponseInterface;
use App\Kernel\Responses\ClientErrorResponse;
use App\Kernel\Interfaces\Databases\ConnectorInterface;
use App\Kernel\Responses\JsonResponse;
use Exception;

abstract class AbstractController
{
    protected ConnectorInterface $connector;
    protected Request $request;

    public function __construct()
    {
        $this->request = Request::getRequestInstance();
    }

    protected function returnError(int $error, ?Exception $e = null): ResponseInterface
    {
        $response = new ClientErrorResponse($error, $e);
                return $response;
    }

    protected function isUserAuth(): bool
    {
        return $this->request->isConnected();
    }

    protected function returnJson(mixed $body = null, ?int $code=200):ResponseInterface
    {
        $response =  new JsonResponse($code);
        if(null !==$body) {
            $response->setBody($body);
        }
        return $response;
    }

} 
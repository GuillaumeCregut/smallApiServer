<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel;

use App\Kernel\Request;

use App\Kernel\Interfaces\ResponseInterface;
use App\Kernel\Responses\ClientErrorResponse;
use App\Kernel\Connector\Interfaces\EntityManagerInterface;
use App\Kernel\Connector\Management\EntityManager;
use App\Kernel\Connector\Management\IdentityMap;
use App\Kernel\Responses\JsonResponse;
use Exception;

abstract class AbstractController
{
    protected Request $request;
    protected EntityManagerInterface $em;

    public function __construct()
    {
        $this->request = Request::getRequestInstance();
        $this->em = EntityManager::getInstance(new IdentityMap());
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
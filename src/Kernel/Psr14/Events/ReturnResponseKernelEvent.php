<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Psr14\Events;

use App\Kernel\Request;
use App\Kernel\Interfaces\ResponseInterface;

class ReturnResponseKernelEvent extends AbstractStoppableEvent
{
     //Launched when kernel will return response to index
     public function __construct(
          private ResponseInterface $response,
          private Request $request
     ) {}

     public function getResponse(): ResponseInterface
     {
          return $this->response;
     }

     public function getRequest(): Request
     {
          return $this->request;
     }
}

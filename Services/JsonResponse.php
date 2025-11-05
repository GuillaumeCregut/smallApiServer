<?php

namespace App\Services;

use App\Interfaces\ResponseInterface;

class JsonResponse extends AbstractResponse
{
    private array $statusMessages = [
        200 => 'HTTP/1.0 200 OK',
        201 => 'HTTP/1.0 201 Created',
    ];
    public function __construct(int $statusCode = 200)
    {
        $this->setStatusCode($statusCode);
        header($this->statusMessages[$statusCode] ?? 'HTTP/1.0 200 OK');
        $this->setHeader('Content-Type', 'application/json');
    }
}
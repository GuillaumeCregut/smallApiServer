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
    }

    public function setBody(mixed $content): void
    {
        $this->body = json_encode($content);
    }

    public function sendReponse(): void
    {
        $this->setHeader('Content-Type', 'application/json');
        header($this->statusMessages[$this->statusCode] ?? 'HTTP/1.0 200 OK');
    }
}

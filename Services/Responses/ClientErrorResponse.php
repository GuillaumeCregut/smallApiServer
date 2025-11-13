<?php

namespace App\Services\Responses;

use App\Kernel\AbstractResponse;
use App\Kernel\Interfaces\ResponseInterface;

class ClientErrorResponse extends AbstractResponse implements ResponseInterface
{
    private array $statusMessages = [
        400 => ['400 - Bad Request', 'HTTP/1.0 400 Bad Request'],
        401 => ['HTTP/1.0 401 Unauthorized', '401 - Unauthorized'],
        403 => ['HTTP/1.0 403 Forbidden', '403 - Forbidden'],
        404 => ['HTTP/1.0 404 Not Found', '404 - Page not found'],
        405 => ['HTTP/1.0 405 Method Not Allowed', '405 - Method Not Allowed'],
        422 => ['HTTP/1.0 422 Unprocessable Entity', '422 - Unprocessable Entity'],
    ];
    public function __construct(int $statusCode = 404)
    {
        $this->setStatusCode($statusCode);
        $this->setBody($this->statusMessages[$statusCode][1] ?? '400 - Bad Request');
    }

    public function setBody(mixed $content): void
    {
        $this->body = $content;
    }

    public function sendReponse(): void
    {
        header($this->statusMessages[$this->statusCode][0] ?? 'HTTP/1.0 400 Bad Request');
    }
}
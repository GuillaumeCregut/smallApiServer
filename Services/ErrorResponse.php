<?php

namespace App\Services;

use App\Interfaces\ResponseInterface;

class ErrorResponse extends AbstractResponse implements ResponseInterface
{
    private array $statusMessages = [
        500 => ['HTTP/1.0 500 Internal Server Error', '500 - Internal Server Error'],
    ];
    public function __construct(int $statusCode = 500)
    {
        $this->setStatusCode(500);
        $this->setBody($this->statusMessages[500][1]);
    }

    public function setBody(mixed $content): void
    {
        $this->body = $content;
    }
    public function sendReponse(): void
    {
        header($this->statusMessages[500][0]);
    }
}
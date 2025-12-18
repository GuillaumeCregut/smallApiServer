<?php

namespace App\Services\Responses;

use App\Kernel\utils\Dumper;
use App\Kernel\AbstractResponse;
use App\Kernel\Interfaces\ResponseInterface;

class HTMLResponse extends AbstractResponse implements ResponseInterface
{
    public function __construct(int $statusCode = 200)
    {
        $this->setStatusCode($statusCode);
    }

    public function setBody(mixed $content): void
    {
        $this->body = $content;
    }

    public function sendReponse(): void
    {
        header($this->statusMessages[$this->statusCode][0] ?? 'HTTP/1.0 400 Bad Request');
        $this->setHeader('Content-Type', 'text/html');
    }

    protected function displayDump(): void
    {
         Dumper::displayHTML();
    }
}
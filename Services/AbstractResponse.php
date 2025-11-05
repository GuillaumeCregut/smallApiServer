<?php

namespace App\Services;
use App\Interfaces\ResponseInterface;

abstract class AbstractResponse implements ResponseInterface
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected string $body = '';

    public function setStatusCode(int $code): void
    {
        $this->statusCode = $code;
    }

    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    public function setBody(string $content): void
    {
        $this->body = $content;
    }

    public function send(): void
    {
        // Send status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Send body
        echo $this->body;
    }
}
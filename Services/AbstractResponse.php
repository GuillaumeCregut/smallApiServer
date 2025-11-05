<?php

namespace App\Services;
use App\Interfaces\ResponseInterface;

abstract class AbstractResponse implements ResponseInterface
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected string $body = '';
    
    abstract public function setBody(mixed $content): void;
    abstract public function sendReponse(): void; 

    public function setStatusCode(int $code): void
    {
        $this->statusCode = $code;
    }

    public function setHeader(string $name, string $value): void
    {
        /*
        HTTP headers are built like this :
        Header-Name: Header Value

        */
        $this->headers[$name] = $value;
    }

    public function send(): void
    {
        // Send status code
        $this->sendReponse();
        http_response_code($this->statusCode);
        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        // Send body
        echo $this->body;
    }
}
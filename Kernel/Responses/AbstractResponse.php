<?php

namespace App\Kernel\Responses;

use App\Kernel\GetEnvDatas;
use App\Kernel\utils\Dumper;
use App\Kernel\Exceptions\KernelException;
use App\Kernel\Interfaces\ResponseInterface;

abstract class AbstractResponse implements ResponseInterface
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected string $body = '';

    abstract public function setBody(mixed $content): void;
    abstract public function sendReponse(): void;
    abstract protected function displayDump(): void;

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

    public function send(): string
    {
        // Send status code
        $this->sendReponse();
        http_response_code($this->statusCode);
        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        $this->DisplayDebugMode();
        // Send body
        return $this->body;
    }

    //for tests purposes
    public function getBody():string
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    private function DisplayDebugMode(): void
    {
        try {
            $isDebug = GetEnvDatas::getEnvInstance()->get('debug_mode', false);
            if ($isDebug && class_exists(Dumper::class)) {
                $this->displayDump();
            }
        } catch (KernelException $e) {
        }
    }
}

<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Responses;

use Exception;
use App\Kernel\utils\Dumper;
use App\Kernel\Responses\AbstractResponse;
use App\Kernel\Interfaces\ResponseInterface;

class ErrorResponse extends AbstractResponse implements ResponseInterface
{
    private array $statusMessages = [
        500 => ['HTTP/1.0 500 Internal Server Error', '500 - Internal Server Error'],
    ];
    public function __construct(int $statusCode = 500, ?bool $debug = false, ?Exception $e = null)
    {
        $this->setStatusCode(500);
        $body = $this->statusMessages[500][1];
        $this->setHeader('Content-Type', 'text/plain');
        if ($debug && (null !== $e)) {
            $this->setHeader('Content-Type', 'application/json');
            $message = $this->makeDebugBody($e);
            $message['body'] = $body;
            $body = json_encode($message);
        }
        $this->setBody($body);
    }

    public function setBody(mixed $content): void
    {
        $this->body = $content;
    }
    public function sendReponse(): void
    {
        header($this->statusMessages[500][0]);
    }

    protected function displayDump(): void
    {

        Dumper::displayHTML();
    }

    private function makeDebugBody(Exception $e): array
    {
        return [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
    }
}

<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Responses;

use App\Kernel\utils\Dumper;
use App\Kernel\Responses\AbstractResponse;


class JsonResponse extends AbstractResponse
{
    private array $statusMessages = [
        200 => 'HTTP/1.1 200 OK',
        201 => 'HTTP/1.1 201 Created',
        204 => 'HTTP/1.1 204 No Content'
    ];
    public function __construct(int $statusCode = 200)
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json');
    }

    public function setBody(mixed $content): self
    {
        $this->body = json_encode($content);
        return $this;
    }

    public function sendReponse(): void
    {
        header($this->statusMessages[$this->statusCode] ?? 'HTTP/1.0 200 OK');
    }

    protected function displayDump(): void
    {
        Dumper::displayJSON();
    }
}

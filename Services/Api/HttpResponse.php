<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Services\Api;

class HttpResponse
{
    private int $statusCode;
    private array $headers;
    private string $body;
    private ?array $parsedData = null;
    private string $contentType;
    private float $executionTime;

    public function __construct(
        int $statusCode,
        array $headers,
        string $body,
        string $contentType = 'application/json',
        float $executionTime = 0
    ) {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
        if(key_exists('Content-Type', $headers)) {
            $this->contentType = explode(';', $headers['Content-Type'])[0];
        } else {
            $this->contentType = $contentType;
        }
        $this->executionTime = $executionTime;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === strtolower($name)) {
                return $value;
            }
        }
        return null;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getData(): ?array
    {
        if ($this->parsedData !== null) {
            return $this->parsedData;
        }
        $this->parsedData = $this->parse();
        return $this->parsedData;
    }

    private function parse(): ?array
    {
        if (empty($this->body)) {
            return null;
        }
        if (strpos($this->contentType, 'application/json') !== false) {
            return json_decode($this->body, true);
        }
        if (strpos($this->contentType, 'application/xml') !== false || 
            strpos($this->contentType, 'text/xml') !== false) {
            return $this->parseXml($this->body);
        }
        return ['raw' => $this->body];
    }

    private function parseXml(string $xml): ?array
    {
        try {
            $simpleXml = simplexml_load_string($xml);
            return json_decode(json_encode($simpleXml), true);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    public function isError(): bool
    {
        return $this->statusCode >= 400;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    public function getStatusMessage(): string
    {
        $messages = [
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
        ];
        return $messages[$this->statusCode] ?? 'Unknown Status';
    }

    public function __toString(): string
    {
        return sprintf(
            "HttpResponse [%d %s] - %s - %d bytes - %.3fs",
            $this->statusCode,
            $this->getStatusMessage(),
            $this->contentType,
            strlen($this->body),
            $this->executionTime
        );
    }
}

<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Services\Api;

use App\Services\Api\Exceptions\NetworkException;
use App\Services\Api\Exceptions\InvalidUrlException;
use Closure;

class HttpClient
{
    private array $defaultHeaders = [];
    private string $authType = 'none'; // none, basic, bearer, apikey
    private string $authValue = '';
    private string $authKeyName = 'X-API-Key'; // Pour authType = apikey
    private int $timeout = 30;
    private int $connectTimeout = 10;
    private bool $verifySSL = true;
    private int $maxRetries = 3;
    private int $retryDelay = 1000; // millisecondes
    private array $retryableStatuses = [408, 429, 500, 502, 503, 504];
    private Closure|null $logger = null;
    private string $userAgent = 'PHP-HttpClient/1.0';

    public function setDefaultHeaders(array $headers): self
    {
        $this->defaultHeaders = $headers;
        return $this;
    }

    public function addDefaultHeader(string $name, string $value): self
    {
        $this->defaultHeaders[$name] = $value;
        return $this;
    }

    public function setBasicAuth(string $username, string $password): self
    {
        $this->authType = 'basic';
        $this->authValue = base64_encode("{$username}:{$password}");
        return $this;
    }

    public function setBearerAuth(string $token): self
    {
        $this->authType = 'bearer';
        $this->authValue = $token;
        return $this;
    }


    public function setApiKeyAuth(string $apiKey, string $headerName = 'X-API-Key'): self
    {
        $this->authType = 'apikey';
        $this->authValue = $apiKey;
        $this->authKeyName = $headerName;
        return $this;
    }

    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    public function setConnectTimeout(int $seconds): self
    {
        $this->connectTimeout = $seconds;
        return $this;
    }

    public function setVerifySSL(bool $verify): self
    {
        $this->verifySSL = $verify;
        return $this;
    }

    public function setMaxRetries(int $count): self
    {
        $this->maxRetries = $count;
        return $this;
    }

    public function setRetryableStatuses(array $statuses): self
    {
        $this->retryableStatuses = $statuses;
        return $this;
    }

    public function setLogger(callable $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    public function setUserAgent(string $agent): self
    {
        $this->userAgent = $agent;
        return $this;
    }

    public function get(string $url, array $query = [], array $headers = []): HttpResponse
    {
        if (!empty($query)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
        }
        return $this->request('GET', $url, null, $headers);
    }

    public function post(string $url, $body = null, array $headers = []): HttpResponse
    {
        return $this->request('POST', $url, $body, $headers);
    }

    public function put(string $url, $body = null, array $headers = []): HttpResponse
    {
        return $this->request('PUT', $url, $body, $headers);
    }

 
    public function patch(string $url, $body = null, array $headers = []): HttpResponse
    {
        return $this->request('PATCH', $url, $body, $headers);
    }

  
    public function delete(string $url, $body = null, array $headers = []): HttpResponse
    {
        return $this->request('DELETE', $url, $body, $headers);
    }

    public function request(
        string $method,
        string $url,
        $body = null,
        array $headers = []
    ): HttpResponse {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidUrlException("Invalid URL: {$url}");
        }

        $allHeaders = array_merge($this->defaultHeaders, $headers);
        $allHeaders['User-Agent'] = $this->userAgent;

        $allHeaders = $this->addAuthHeader($allHeaders);

        $lastException = null;
        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $response = $this->executeRequest($method, $url, $body, $allHeaders);
                if (
                    $attempt < $this->maxRetries &&
                    in_array($response->getStatusCode(), $this->retryableStatuses)
                ) {
                    $this->log("Attempt {$attempt}/" . $this->maxRetries .
                        " failed with status " . $response->getStatusCode() .
                        ". Retrying...");
                    usleep($this->retryDelay * pow(2, $attempt) * 1000);
                    continue;
                }

                return $response;
            } catch (NetworkException $e) {
                $lastException = $e;

                if ($attempt < $this->maxRetries) {
                    $this->log("Network error on attempt {$attempt}: " . $e->getMessage() .
                        ". Retrying...");
                    usleep($this->retryDelay * pow(2, $attempt) * 1000);
                    continue;
                }
            }
        }

        if ($lastException) {
            throw $lastException;
        }
        throw new NetworkException("Request failed after {$this->maxRetries} retries");
    }

    private function executeRequest(
        string $method,
        string $url,
        $body,
        array $headers
    ): HttpResponse {
        $startTime = microtime(true);

        $ch = curl_init($url);
        if ($ch === false) {
            throw new NetworkException("Failed to initialize cURL");
        }

        try {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            
            if (!$this->verifySSL) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }

            if ($method !== 'GET') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }

            $headerList = [];
            
            if ($body !== null && !isset($headers['Content-Type'])) {
                $headers['Content-Type'] = 'application/json';
            }
            
            foreach ($headers as $name => $value) {
                $headerList[] = "{$name}: {$value}";
            }

            if ($body !== null) {
                $bodyString = is_array($body) ? json_encode($body) : (string)$body;
                curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyString);
            }

            if (!empty($headerList)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headerList);
            }

            $responseHeaders = [];
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$responseHeaders) {
                $len = strlen($header);
                if (strpos($header, ':') !== false) {
                    [$name, $value] = explode(':', $header, 2);
                    $responseHeaders[trim($name)] = trim($value);
                }
                return $len;
            });

            $body = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?? 'application/json';

            if ($body === false) {
                $error = curl_error($ch);
                $errno = curl_errno($ch);
                throw new NetworkException(
                    "cURL error: {$error}",
                    "Error #{$errno}: {$error}"
                );
            }

            $executionTime = microtime(true) - $startTime;

            $this->log(sprintf(
                "%s %s -> %d (%s) in %.3fs",
                $method,
                $url,
                $statusCode,
                $contentType,
                $executionTime
            ));

            return new HttpResponse(
                (int)$statusCode,
                $responseHeaders,
                (string)$body,
                $contentType,
                $executionTime
            );
        } finally {
        }
    }

    private function addAuthHeader(array $headers): array
    {
        match ($this->authType) {
            'basic' => $headers['Authorization'] = "Basic {$this->authValue}",
            'bearer' => $headers['Authorization'] = "Bearer {$this->authValue}",
            'apikey' => $headers[$this->authKeyName] = $this->authValue,
            default => null
        };

        return $headers;
    }

    private function log(string $message): void
    {
        if ($this->logger !== null) {
            call_user_func($this->logger, "[" . date('Y-m-d H:i:s') . "] {$message}");
        }
    }
}

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

    /**
     * Configure les headers par défaut
     */
    public function setDefaultHeaders(array $headers): self
    {
        $this->defaultHeaders = $headers;
        return $this;
    }

    /**
     * Ajoute un header par défaut
     */
    public function addDefaultHeader(string $name, string $value): self
    {
        $this->defaultHeaders[$name] = $value;
        return $this;
    }

    /**
     * Configure l'authentification Basic (user:password)
     */
    public function setBasicAuth(string $username, string $password): self
    {
        $this->authType = 'basic';
        $this->authValue = base64_encode("{$username}:{$password}");
        return $this;
    }

    /**
     * Configure l'authentification Bearer Token
     */
    public function setBearerAuth(string $token): self
    {
        $this->authType = 'bearer';
        $this->authValue = $token;
        return $this;
    }

    /**
     * Configure l'authentification API Key
     */
    public function setApiKeyAuth(string $apiKey, string $headerName = 'X-API-Key'): self
    {
        $this->authType = 'apikey';
        $this->authValue = $apiKey;
        $this->authKeyName = $headerName;
        return $this;
    }

    /**
     * Configure les timeouts (en secondes)
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Configure le timeout de connexion
     */
    public function setConnectTimeout(int $seconds): self
    {
        $this->connectTimeout = $seconds;
        return $this;
    }

    /**
     * Désactive/active la vérification SSL (déconseillé en production)
     */
    public function setVerifySSL(bool $verify): self
    {
        $this->verifySSL = $verify;
        return $this;
    }

    /**
     * Configure le nombre de retries automatiques
     */
    public function setMaxRetries(int $count): self
    {
        $this->maxRetries = $count;
        return $this;
    }

    /**
     * Configure les statuts HTTP à retry automatiquement
     */
    public function setRetryableStatuses(array $statuses): self
    {
        $this->retryableStatuses = $statuses;
        return $this;
    }

    /**
     * Configure un logger (callable)
     */
    public function setLogger(callable $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Configure le User-Agent
     */
    public function setUserAgent(string $agent): self
    {
        $this->userAgent = $agent;
        return $this;
    }

    /**
     * Effectue une requête GET
     */
    public function get(string $url, array $query = [], array $headers = []): HttpResponse
    {
        if (!empty($query)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
        }
        return $this->request('GET', $url, null, $headers);
    }

    /**
     * Effectue une requête POST
     */
    public function post(string $url, $body = null, array $headers = []): HttpResponse
    {
        return $this->request('POST', $url, $body, $headers);
    }

    /**
     * Effectue une requête PUT
     */
    public function put(string $url, $body = null, array $headers = []): HttpResponse
    {
        return $this->request('PUT', $url, $body, $headers);
    }

    /**
     * Effectue une requête PATCH
     */
    public function patch(string $url, $body = null, array $headers = []): HttpResponse
    {
        return $this->request('PATCH', $url, $body, $headers);
    }

    /**
     * Effectue une requête DELETE
     */
    public function delete(string $url, $body = null, array $headers = []): HttpResponse
    {
        return $this->request('DELETE', $url, $body, $headers);
    }

    /**
     * Effectue une requête HTTP générique
     */
    public function request(
        string $method,
        string $url,
        $body = null,
        array $headers = []
    ): HttpResponse {
        // Valider l'URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidUrlException("Invalid URL: {$url}");
        }

        // Fusionner les headers
        $allHeaders = array_merge($this->defaultHeaders, $headers);
        $allHeaders['User-Agent'] = $this->userAgent;

        // Ajouter l'authentification
        $allHeaders = $this->addAuthHeader($allHeaders);

        // Boucle de retry
        $lastException = null;
        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $response = $this->executeRequest($method, $url, $body, $allHeaders);

                // Vérifier si on doit retry
                if ($attempt < $this->maxRetries && 
                    in_array($response->getStatusCode(), $this->retryableStatuses)) {
                    $this->log("Attempt {$attempt}/". $this->maxRetries . 
                        " failed with status " . $response->getStatusCode() . 
                        ". Retrying...");
                    
                    // Attendre avant le retry (backoff exponentiel)
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

        // Si on arrive ici, tous les retries ont échoué
        if ($lastException) {
            throw $lastException;
        }

        throw new NetworkException("Request failed after {$this->maxRetries} retries");
    }

    /**
     * Exécute réellement la requête avec cURL
     */
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
            // Configuration basique
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

            // SSL
            if (!$this->verifySSL) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }

            // Méthode HTTP
            if ($method !== 'GET') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }

            // Body
            if ($body !== null) {
                $bodyString = is_array($body) ? json_encode($body) : (string)$body;
                curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyString);
            }

            // Headers
            $headerList = [];
            foreach ($headers as $name => $value) {
                $headerList[] = "{$name}: {$value}";
            }
            if (!empty($headerList)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headerList);
            }

            // Capturer les headers de réponse
            $responseHeaders = [];
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$responseHeaders) {
                $len = strlen($header);
                // Ignorer les lignes vides et la ligne de status
                if (strpos($header, ':') !== false) {
                    [$name, $value] = explode(':', $header, 2);
                    $responseHeaders[trim($name)] = trim($value);
                }
                return $len;
            });

            // Exécuter la requête
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

    /**
     * Ajoute le header d'authentification
     */
    private function addAuthHeader(array $headers): array
    {
        match($this->authType) {
            'basic' => $headers['Authorization'] = "Basic {$this->authValue}",
            'bearer' => $headers['Authorization'] = "Bearer {$this->authValue}",
            'apikey' => $headers[$this->authKeyName] = $this->authValue,
            default => null
        };

        return $headers;
    }

    /**
     * Enregistre un message dans le logger
     */
    private function log(string $message): void
    {
        if ($this->logger !== null) {
            call_user_func($this->logger, "[" . date('Y-m-d H:i:s') . "] {$message}");
        }
    }
}

<?php

namespace App\Services\Api\Exceptions;

use App\Services\Api\Exceptions\HttpException;

class NetworkException extends HttpException
{
    private string $curlError;

    public function __construct(string $message, string $curlError = '')
    {
        parent::__construct($message);
        $this->curlError = $curlError;
    }

    public function getCurlError(): string
    {
        return $this->curlError;
    }
}
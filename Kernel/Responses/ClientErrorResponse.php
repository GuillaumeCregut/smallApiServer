<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Responses;

use App\Kernel\Responses\AbstractResponse;
use App\Kernel\Interfaces\ResponseInterface;

class ClientErrorResponse extends AbstractResponse implements ResponseInterface
{
    private array $statusMessages = [
        400 => ['HTTP/1.1 400 Bad Request', '400 - Bad Request'],
        401 => ['HTTP/1.1 401 Unauthorized', '401 - Unauthorized'],
        402 => ['HTTP/1.1 402 Payment Required', '402 - Payment Required'],
        403 => ['HTTP/1.1 403 Forbidden', '403 - Forbidden'],
        404 => ['HTTP/1.1 404 Not Found', '404 - Page not found'],
        405 => ['HTTP/1.1 405 Method Not Allowed', '405 - Method Not Allowed'],
        406 => ['HTTP/1.1 406 Not Acceptable', '406 - Not Acceptable'],
        407 => ['HTTP/1.1 407 Proxy Authentication Required', '407 - Proxy Authentication Required'],
        408 => ['HTTP/1.1 408 Request Timeout', '408 - equest Timeout'],
        409 => ['HTTP/1.1 409 Conflict', '409 - Conflict'],
        410 => ['HTTP/1.1 410 Gone', ' 410 - Gone'],
        411 => ['HTTP/1.1 411 Length Required', '411 - Length Required'],
        412 => ['HTTP/1.1 412 Precondition Failed', '412 - Precondition Failed'],
        413 => ['HTTP/1.1 413 Content Too Large', '413 - Content Too Large'],
        414 => ['HTTP/1.1 414 URI Too Long', '414 - URI Too Long'],
        415 => ['HTTP/1.1 415 Unsupported Media Type', '415 - Unsupported Media Type'],
        416 => ['HTTP/1.1 416 Range Not Satisfiable', '416 - Range Not Satisfiable'],
        417 => ['HTTP/1.1 417 Expectation Failed', '417 - Expectation Failed'],
        418 => ["HTTP/1.1 418 I'm a teapot", "418 - I'm a teapot"],
        421 => ['HTTP/1.1 421 Misdirected Request', '421 - Misdirected Request'],
        422 => ['HTTP/1.1 422 Unprocessable Entity', '422 - Unprocessable Entity'],
        423 => ['HTTP/1.1 423 Locked', '423 - Locked'],
        424 => ['HTTP/1.1 424 Failed Dependency', '424 - Failed Dependency'],
        425 => ['HTTP/1.1 425 Too Early', '425 - Too Early'],
        426 => ['HTTP/1.1 426 Upgrade Required', '426 - Upgrade Required'],
        428 => ['HTTP/1.1 428 Precondition Required', '428 - Precondition Required'],
        429 => ['HTTP/1.1 429 Too Many Requests', '429 - Too Many Requests'],
        431 => ['HTTP/1.1 431 Request Header Fields Too Large', '431 - Request Header Fields Too Large'],
        451 => ['HTTP/1.1 451 Unavailable For Legal Reasons', '451 - Unavailable For Legal Reasons'],
    ];


    public function __construct(int $statusCode = 404)
    {
        $listCodes = [
            ...range(400, 418),
            ...range(421, 426),
            428,
            429,
            431,
            451
        ];
        if (!in_array($statusCode, $listCodes)) {
            echo "pas dans le tableau $statusCode";
            $statusCode = 400;
        }
        $this->setStatusCode($statusCode);
        $this->setBody($this->statusMessages[$statusCode][1] ?? '400 - Bad Request');
        $this->setHeader('Content-Type', 'text/plain');
    }

    public function setBody(mixed $content): self
    {
        json_decode($content);
        $isJson = json_last_error() === JSON_ERROR_NONE;
        if ($isJson) {
            $this->setHeader('Content-Type', 'application/json');
        }
        $this->body = $content;
        return $this;
    }

    public function sendReponse(): void
    {
        header($this->statusMessages[$this->statusCode][0] ?? 'HTTP/1.1 400 Bad Request');
    }

    protected function displayDump(): void {}
}

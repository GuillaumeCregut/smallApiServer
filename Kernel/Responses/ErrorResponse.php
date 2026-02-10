<?php

namespace App\Kernel\Responses;

use App\Kernel\utils\Dumper;
use App\Kernel\AbstractResponse;
use App\Kernel\GetEnvDatas;
use App\Kernel\Interfaces\ResponseInterface;
use Exception;

class ErrorResponse extends AbstractResponse implements ResponseInterface
{
    private array $statusMessages = [
        500 => ['HTTP/1.0 500 Internal Server Error', '500 - Internal Server Error'],
    ];
    public function __construct(int $statusCode = 500, ?Exception $e=null)
    {
        $this->setStatusCode(500);
        $body = $this->statusMessages[500][1];
        $debug = GetEnvDatas::getEnvInstance()->get('debug_mode', false);
        if ($debug && (null !== $e)) {
            $body .= $this->makeDebugBody($e);
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

    private function makeDebugBody(Exception $e): string
    {
        return '';
    }
}
<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Responses\ErrorResponse;

class ErrorResponseTest extends TestCase
{
    public function testWithExceptionAndDebug(): void
    {
        $exception = new Exception('Sample Message', 20);
        $response = new ErrorResponse(500, true , $exception);
        $body = $response->getBody();
        $this->assertJson($body);
        $arrayBody = json_decode($body, true);
        $this->assertArrayHasKey('message', $arrayBody);
        $this->assertArrayHasKey('body', $arrayBody);
        $this->assertArrayHasKey('code', $arrayBody);
        $this->assertArrayHasKey('file', $arrayBody);
        $this->assertArrayHasKey('line', $arrayBody);
        $headers = $response->getHeaders();
        $this->assertIsArray($headers);
        $this->assertArrayHasKey('content-type', $headers);
        $this->assertSame('application/json', $headers['content-type']);
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testWithException(): void
    {
        $exception = new Exception('Sample Message', 20);
        $response = new ErrorResponse(500, false , $exception);
        $body = $response->getBody();
        $this->assertStringContainsString('500 - Internal Server',$body);
        $headers = $response->getHeaders();
        $this->assertIsArray($headers);
        $this->assertArrayHasKey('content-type', $headers);
        $this->assertSame('text/plain', $headers['content-type']);
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testWithChangeCode(): void
    {
        $exception = new Exception('Sample Message', 20);
        $response = new ErrorResponse(200, false , $exception);
        $body = $response->getBody();
        $this->assertStringContainsString('500 - Internal Server',$body);
        $this->assertSame(500, $response->getStatusCode());
    }

}

<?php

use App\Kernel\Responses\ClientErrorResponse;
use PHPUnit\Framework\TestCase;

class ClientErrorResponseTest extends TestCase
{

    public function test400Error(): void
    {
        $response = new ClientErrorResponse(400);
        $body = $response->getBody();
        $this->assertStringContainsString('400 - Bad Request',$body);
        $headers = $response->getHeaders();
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('text/plain', $headers['content-type']);
    }
    
    public function test467Error(): void
    {
        $response = new ClientErrorResponse(467);
        $body = $response->getBody();
        $this->assertStringContainsString('400 - Bad Request',$body);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testWithBody(): void
    {
        $response = new ClientErrorResponse(400);
        $response->setBody(json_encode(['test', 'test2']));
        $body = $response->getBody();
        $this->assertJson($body);
        $headers = $response->getHeaders();
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/json', $headers['content-type']);
    }
}
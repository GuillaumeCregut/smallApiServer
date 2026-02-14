<?php

use PHPUnit\Framework\TestCase;
use App\Services\Api\HttpResponse;

class HttpResponseTest extends TestCase
{
    public function testSuccessResponse()
    {
        $response = new HttpResponse(
            200,
            ['Content-Type' => 'application/json'],
            '{"name":"John","age":30}'
        );

        $this->assertTrue($response->isSuccess());
        $this->assertFalse($response->isError());
        $this->assertFalse($response->isClientError());
        $this->assertFalse($response->isServerError());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getStatusMessage());
    }

    public function testClientErrorResponse()
    {
        $response = new HttpResponse(
            404,
            ['Content-Type' => 'application/json'],
            '{"error":"Not found"}'
        );

        $this->assertFalse($response->isSuccess());
        $this->assertTrue($response->isError());
        $this->assertTrue($response->isClientError());
        $this->assertFalse($response->isServerError());
        $this->assertEquals('Not Found', $response->getStatusMessage());
    }

    public function testServerErrorResponse()
    {
        $response = new HttpResponse(
            500,
            ['Content-Type' => 'application/json'],
            '{"error":"Internal server error"}'
        );

        $this->assertFalse($response->isSuccess());
        $this->assertTrue($response->isError());
        $this->assertFalse($response->isClientError());
        $this->assertTrue($response->isServerError());
    }

    public function testJsonParsing()
    {
        $response = new HttpResponse(
            200,
            ['Content-Type' => 'application/json'],
            '{"name":"John","age":30,"roles":["admin","user"]}'
        );

        $data = $response->getData();
        $this->assertIsArray($data);
        $this->assertEquals('John', $data['name']);
        $this->assertEquals(30, $data['age']);
        $this->assertCount(2, $data['roles']);
    }

    public function testXmlParsing()
    {
        $xml = '<?xml version="1.0"?><root><name>John</name><age>30</age></root>';
        $response = new HttpResponse(
            200,
            ['Content-Type' => 'application/xml'],
            $xml
        );

        $data = $response->getData();
        $this->assertIsArray($data);
        $this->assertEquals('John', $data['name']);
        $this->assertEquals('30', $data['age']);
    }
    
    public function testHeadersRetrieval()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Custom-Header' => 'custom-value',
            'Authorization' => 'Bearer token123'
        ];

        $response = new HttpResponse(200, $headers, '{}');

        $this->assertEquals($headers, $response->getHeaders());
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
        $this->assertEquals('custom-value', $response->getHeader('X-Custom-Header'));
        // Case-insensitive
        $this->assertEquals('custom-value', $response->getHeader('x-custom-header'));
        $this->assertNull($response->getHeader('Non-Existent'));
    }

    public function testExecutionTime()
    {
        $response = new HttpResponse(
            200,
            [],
            '{}',
            'application/json',
            0.523
        );

        $this->assertEquals(0.523, $response->getExecutionTime());
    }

    public function testEmptyBody()
    {
        $response = new HttpResponse(204, [], '');

        $this->assertNull($response->getData());
        $this->assertEquals('', $response->getBody());
    }
}


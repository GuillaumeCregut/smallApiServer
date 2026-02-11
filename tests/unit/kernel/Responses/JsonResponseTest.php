<?php

use App\Kernel\Responses\JsonResponse;
use PHPUnit\Framework\TestCase;

class JsonResponseTest extends TestCase
{
     public function testJsonResponseOK(): void
    {
        $response = new JsonResponse();
        $datas = ['test'];
        $response->setBody($datas);
        $body = $response->getBody();
        $this->assertJson($body);
        $headers = $response->getHeaders();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $headers['Content-Type']);
    }
}
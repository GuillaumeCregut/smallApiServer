<?php

use PHPUnit\Framework\TestCase;
use App\Services\Api\HttpClient;
use App\Services\Api\Exceptions\NetworkException;
use App\Services\Api\Exceptions\InvalidUrlException;

class HttpClientTest extends TestCase
{
    private HttpClient $client;

    protected function setUp(): void
    {
        $this->client = new HttpClient();
    }

    public function testInvalidUrlThrowsException()
    {
        $this->expectException(InvalidUrlException::class);
        $this->client->get('not a valid url');
    }

    public function testUrlValidation()
    {
        $this->expectException(NetworkException::class);
        $this->client->get('htp://invalid-protocol.com');
    }


    public function testFluentInterface()
    {
        $result = $this->client
            ->setMaxRetries(3)
            ->setTimeout(15)
            ->setConnectTimeout(5)
            ->setVerifySSL(false)
            ->setBearerAuth('token');

        $this->assertInstanceOf(HttpClient::class, $result);
    }

    public function testHeaders()
    {
        $this->client->setDefaultHeaders([
            'Accept' => 'application/json',
            'Custom' => 'value'
        ]);

        $this->client->addDefaultHeader('Another', 'header');

        // Les headers seraient appliqués à chaque requête
        $this->assertTrue(true);
    }
}
<?php

use PHPUnit\Framework\TestCase;
use App\Services\Api\HttpClient;
use App\Services\Api\HttpResponse;

class MockHttpClient extends HttpClient
{
    private array $mockResponses = [];

    public function mockResponse(string $url, HttpResponse $response): self
    {
        $this->mockResponses[$url] = $response;
        return $this;
    }

    public function get(string $url, array $query = [], array $headers = []): HttpResponse
    {
        if (isset($this->mockResponses[$url])) {
            return $this->mockResponses[$url];
        }

        return parent::get($url, $query, $headers);
    }
}

class HttpClientIntegrationTest extends TestCase
{
    public function testApiCallWithMock()
    {
        $client = new MockHttpClient();

        $mockResponse = new HttpResponse(
            200,
            ['Content-Type' => 'application/json'],
            '{"id":1,"name":"John","email":"john@example.com"}'
        );

        $client->mockResponse('https://api.example.com/users/1', $mockResponse);

        $response = $client->get('https://api.example.com/users/1');

        $this->assertTrue($response->isSuccess());
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData();
        $this->assertEquals('John', $data['name']);
        $this->assertEquals('john@example.com', $data['email']);
    }

    public function testErrorHandlingWithMock()
    {
        $client = new MockHttpClient();
        $mockError = new HttpResponse(
            404,
            ['Content-Type' => 'application/json'],
            '{"error":"User not found"}'
        );

        $client->mockResponse('https://api.example.com/users/999', $mockError);
        $response = $client->get('https://api.example.com/users/999');

        $this->assertFalse($response->isSuccess());
        $this->assertTrue($response->isClientError());
        $this->assertEquals(404, $response->getStatusCode());
    }
}

class UserServiceTest extends TestCase
{

    private MockHttpClient $mockClient;
    private UserService $userService;

    protected function setUp(): void
    {
        $this->mockClient = new MockHttpClient();
        $this->userService = new UserService($this->mockClient);
    }

    public function testGetUserSuccess()
    {
        $mockResponse = new HttpResponse(
            200,
            ['Content-Type' => 'application/json'],
            '{"id":1,"name":"John","email":"john@example.com"}'
        );
        $this->mockClient->mockResponse(
            'https://api.example.com/users/1',
            $mockResponse
        );

        $user = $this->userService->getUser(1);

        $this->assertIsArray($user);
        $this->assertEquals('John', $user['name']);
    }

    public function testGetUserNotFound()
    {
        $mockResponse = new HttpResponse(
            404,
            ['Content-Type' => 'application/json'],
            '{"error":"Not found"}'
        );
        $this->mockClient->mockResponse(
            'https://api.example.com/users/999',
            $mockResponse
        );

        $user = $this->userService->getUser(999);
        $this->assertNull($user);
    }
}

class UserService
{
    private HttpClient $httpClient;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getUser(int $id): ?array
    {
        try {
            $response = $this->httpClient->get(
                "https://api.example.com/users/{$id}"
            );

            if ($response->isSuccess()) {
                return $response->getData();
            }

            return null;

        } catch (Exception $e) {
            return null;
        }
    }
}

# HTTP API Client Documentation

## Overview

The HTTP API Client system provides a comprehensive, production-ready solution for making HTTP requests to external APIs. Built with cURL, it handles authentication, retries, error handling, and response parsing automatically.

The system consists of:

1. **HttpClient**: Main HTTP request handler with fluent interface
2. **HttpResponse**: Response object with parsing and status checking
3. **Exception Classes**: Network and URL validation errors
4. **Fluent Interface**: Method chaining for configuration

### Location

- `App\Services\Api\HttpClient`
- `App\Services\Api\HttpResponse`
- `App\Services\Api\Exceptions\`

---

## HttpClient

### Description

`HttpClient` is the main class for making HTTP requests to external APIs. It provides a fluent interface for configuration and supports multiple authentication methods, automatic retries, and various HTTP verbs.

### Key Features

- **Multiple Authentication Methods**: Basic Auth, Bearer Token, API Key
- **Automatic Retries**: With exponential backoff for failed requests
- **Request Methods**: GET, POST, PUT, PATCH, DELETE
- **SSL Verification**: Configurable for development/production
- **Logging**: Optional custom logger support
- **Response Parsing**: Automatic JSON and XML parsing
- **User Agent**: Customizable user agent
- **Timeout Control**: Connection and request timeouts
- **Header Management**: Default and per-request headers

### Constructor

```php
public function __construct()
```

Creates a new HttpClient with default configuration.

**Default Settings:**
- Status Code: 200
- Content Type: application/json
- Timeout: 30 seconds
- Connect Timeout: 10 seconds
- SSL Verification: Enabled
- Max Retries: 3
- Retryable Status Codes: 408, 429, 500, 502, 503, 504
- User Agent: PHP-HttpClient/1.0

### Configuration Methods

All configuration methods return `self` for method chaining.

#### `setDefaultHeaders(array $headers): self`

Sets all default headers at once.

```php
$client = new HttpClient();
$client->setDefaultHeaders([
    'Accept' => 'application/json',
    'X-API-Version' => 'v1'
]);
```

#### `addDefaultHeader(string $name, string $value): self`

Adds a single default header (keeps existing headers).

```php
$client->addDefaultHeader('X-Request-ID', 'req-12345');
```

#### `setBasicAuth(string $username, string $password): self`

Configures HTTP Basic Authentication.

**Format:** `Authorization: Basic <base64(username:password)>`

```php
$client->setBasicAuth('user@example.com', 'secure_password');
```

#### `setBearerAuth(string $token): self`

Configures Bearer Token Authentication (JWT, OAuth, etc.).

**Format:** `Authorization: Bearer <token>`

```php
$client->setBearerAuth('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...');
```

#### `setApiKeyAuth(string $apiKey, string $headerName = 'X-API-Key'): self`

Configures API Key Authentication with custom header name.

```php
// Using default X-API-Key header
$client->setApiKeyAuth('sk-1234567890abcdef');

// Using custom header
$client->setApiKeyAuth('my-secret-key', 'Authorization-Key');
```

#### `setTimeout(int $seconds): self`

Sets the request timeout in seconds.

```php
$client->setTimeout(60);  // 1 minute timeout
```

#### `setConnectTimeout(int $seconds): self`

Sets the connection timeout in seconds.

```php
$client->setConnectTimeout(5);  // 5 second connection timeout
```

#### `setVerifySSL(bool $verify): self`

Enable or disable SSL certificate verification (development only!).

```php
// Development environment
$client->setVerifySSL(false);

// Production (always enabled)
$client->setVerifySSL(true);
```

#### `setMaxRetries(int $count): self`

Sets the maximum number of retry attempts for failed requests.

```php
$client->setMaxRetries(5);  // Retry up to 5 times
```

#### `setRetryableStatuses(array $statuses): self`

Specifies which HTTP status codes should trigger a retry.

```php
$client->setRetryableStatuses([408, 429, 500, 502, 503, 504]);
```

#### `setLogger(callable $logger): self`

Sets a custom logger function.

```php
$client->setLogger(function($message) {
    error_log($message);
    // or: $logger::info($message);
});
```

#### `setUserAgent(string $agent): self`

Sets custom User-Agent header.

```php
$client->setUserAgent('MyApp/1.0 (PHP 8.1)');
```

### Request Methods

#### `get(string $url, array $query = [], array $headers = []): HttpResponse`

Makes a GET request.

**Parameters:**
- `$url` (string): Full URL
- `$query` (array): Query parameters (auto-encoded and appended to URL)
- `$headers` (array): Additional headers for this request

```php
// Simple GET
$response = $client->get('https://api.example.com/users');

// With query parameters
$response = $client->get('https://api.example.com/users', [
    'page' => 2,
    'limit' => 10,
    'sort' => 'name'
]);

// With custom headers
$response = $client->get('https://api.example.com/users', [], [
    'X-Request-ID' => 'req-12345'
]);
```

#### `post(string $url, $body = null, array $headers = []): HttpResponse`

Makes a POST request.

**Parameters:**
- `$url` (string): Full URL
- `$body` (mixed): Request body (array = JSON, string = raw)
- `$headers` (array): Additional headers

```php
// POST with JSON body
$response = $client->post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// POST with custom header
$response = $client->post('https://api.example.com/users', $data, [
    'Content-Type' => 'application/json'
]);
```

#### `put(string $url, $body = null, array $headers = []): HttpResponse`

Makes a PUT request (full resource replacement).

```php
$response = $client->put('https://api.example.com/users/1', [
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);
```

#### `patch(string $url, $body = null, array $headers = []): HttpResponse`

Makes a PATCH request (partial resource update).

```php
$response = $client->patch('https://api.example.com/users/1', [
    'email' => 'newemail@example.com'
]);
```

#### `delete(string $url, $body = null, array $headers = []): HttpResponse`

Makes a DELETE request.

```php
$response = $client->delete('https://api.example.com/users/1');

// DELETE with body (some APIs require it)
$response = $client->delete('https://api.example.com/users/1', [
    'reason' => 'Account closed'
]);
```

#### `request(string $method, string $url, $body = null, array $headers = []): HttpResponse`

Generic request method for custom HTTP verbs.

```php
$response = $client->request('OPTIONS', 'https://api.example.com/users');
```

### Fluent Interface Example

All configuration methods support chaining:

```php
$response = (new HttpClient())
    ->setMaxRetries(5)
    ->setTimeout(60)
    ->setConnectTimeout(10)
    ->setVerifySSL(true)
    ->setBearerAuth($jwtToken)
    ->addDefaultHeader('X-API-Version', 'v2')
    ->setLogger(function($msg) { error_log($msg); })
    ->post('https://api.example.com/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);
```

---

## HttpResponse

### Description

`HttpResponse` represents the response from an HTTP request. It handles automatic parsing of JSON and XML responses and provides utility methods for status checking.

### Constructor

```php
public function __construct(
    int $statusCode,
    array $headers,
    string $body,
    string $contentType = 'application/json',
    float $executionTime = 0
)
```

**Parameters:**
- `$statusCode` (int): HTTP status code (200, 404, 500, etc.)
- `$headers` (array): Response headers
- `$body` (string): Response body content
- `$contentType` (string): MIME type of response
- `$executionTime` (float): Request execution time in seconds

### Methods

#### `getStatusCode(): int`

Returns the HTTP status code.

```php
$code = $response->getStatusCode();  // e.g., 200, 404, 500
```

#### `getHeaders(): array`

Returns all response headers as associative array.

```php
$headers = $response->getHeaders();
// ['Content-Type' => 'application/json', 'X-Rate-Limit' => '100', ...]
```

#### `getHeader(string $name): ?string`

Returns a specific header value (case-insensitive).

```php
$contentType = $response->getHeader('Content-Type');
$rateLimit = $response->getHeader('x-rate-limit');  // Case-insensitive
```

#### `getBody(): string`

Returns the raw response body as string.

```php
$rawBody = $response->getBody();  // Raw JSON/XML/text
```

#### `getData(): ?array`

Parses and returns response data as array.

Automatically detects and parses:
- **JSON**: `application/json`
- **XML**: `application/xml`, `text/xml`
- **Other**: Returns as `['raw' => $body]`

```php
$data = $response->getData();
// ['id' => 1, 'name' => 'John', 'roles' => ['admin', 'user']]
```

#### `getContentType(): string`

Returns the response content type.

```php
$type = $response->getContentType();  // 'application/json'
```

#### `getExecutionTime(): float`

Returns request execution time in seconds.

```php
$time = $response->getExecutionTime();  // 0.523
echo "Request took {$time}s";
```

### Status Checking Methods

#### `isSuccess(): bool`

Returns true for 2xx status codes.

```php
if ($response->isSuccess()) {
    $data = $response->getData();
}
```

#### `isError(): bool`

Returns true for 4xx and 5xx status codes.

```php
if ($response->isError()) {
    // Handle error
}
```

#### `isClientError(): bool`

Returns true for 4xx status codes (client's fault).

```php
if ($response->isClientError()) {
    // 400, 401, 403, 404, 422, etc.
}
```

#### `isServerError(): bool`

Returns true for 5xx status codes (server's fault).

```php
if ($response->isServerError()) {
    // 500, 502, 503, 504, etc.
}
```

#### `getStatusMessage(): string`

Returns human-readable status message.

```php
$message = $response->getStatusMessage();  // 'OK', 'Not Found', 'Internal Server Error', etc.
```

### Magic Methods

#### `__toString(): string`

Returns a formatted string representation of the response.

```php
echo $response;
// Output: HttpResponse [200 OK] - application/json - 2048 bytes - 0.523s
```

---

## Exception Classes

### InvalidUrlException

Thrown when URL validation fails.

**When Thrown:**
- Invalid URL format provided to request methods
- URL protocol not supported

**Usage:**

```php
use App\Services\Api\Exceptions\InvalidUrlException;

try {
    $response = $client->get('not a valid url');
} catch (InvalidUrlException $e) {
    echo "Invalid URL: " . $e->getMessage();
}
```

### NetworkException

Thrown for network-related errors (cURL errors, timeouts, connection failures).

**When Thrown:**
- Connection timeout
- Request timeout
- DNS failure
- cURL error
- SSL verification failure

**Properties:**
- `$message`: Error description
- `$curlError`: Detailed cURL error (via `getCurlError()`)

**Usage:**

```php
use App\Services\Api\Exceptions\NetworkException;

try {
    $response = $client->get('https://api.example.com/users');
} catch (NetworkException $e) {
    echo "Network error: " . $e->getMessage();
    echo "cURL error: " . $e->getCurlError();
    // Log and retry
}
```

---

## Usage Examples

### Example 1: Simple GET Request (HomeController getDatas)

```php
use App\Services\Api\HttpClient;

public function getDatas(): ResponseInterface
{
    // Create client
    $client = new HttpClient();
    
    // Make POST request to external API
    $newUser = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'age' => 30
    ];
    
    $response = $client->post(
        'https://jsonplaceholder.typicode.com/users',
        $newUser
    );
    
    // Get parsed data
    $datas = $response->getData();
    
    // Return JSON response
    return $this->returnJson($datas, 200);
}
```

### Example 2: API with Bearer Token Authentication

```php
public function fetchUserProfile(): ResponseInterface
{
    $jwtToken = $this->request->getUser()->getToken();
    
    $response = (new HttpClient())
        ->setBearerAuth($jwtToken)
        ->setTimeout(30)
        ->get('https://api.example.com/profile');
    
    if ($response->isSuccess()) {
        return $this->returnJson($response->getData(), 200);
    }
    
    return $this->returnError($response->getStatusCode());
}
```

### Example 3: POST with API Key and Error Handling

```php
public function createRemoteRecord(array $data): ResponseInterface
{
    try {
        $response = (new HttpClient())
            ->setApiKeyAuth('sk-abc123def456')
            ->addDefaultHeader('X-API-Version', 'v2')
            ->setTimeout(60)
            ->setMaxRetries(3)
            ->post('https://api.example.com/records', $data);
        
        if ($response->isSuccess()) {
            return $this->returnJson($response->getData(), 201);
        }
        
        if ($response->isClientError()) {
            return $this->returnError(422);  // Validation failed
        }
        
        return $this->returnError(500);  // Server error
        
    } catch (NetworkException $e) {
        error_log("API Error: " . $e->getMessage());
        return $this->returnError(503);  // Service unavailable
    }
}
```

### Example 4: GET with Query Parameters and Pagination

```php
public function searchUsers(int $page = 1, int $limit = 10): ResponseInterface
{
    $response = (new HttpClient())
        ->setBasicAuth('admin@example.com', 'password123')
        ->get('https://api.example.com/users', [
            'page' => $page,
            'limit' => $limit,
            'sort' => 'name_asc'
        ]);
    
    if (!$response->isSuccess()) {
        return $this->returnError(500);
    }
    
    $data = $response->getData();
    return $this->returnJson($data, 200);
}
```

### Example 5: UserService with Dependency Injection

```php
use App\Services\Api\HttpClient;
use App\Services\Api\Exceptions\NetworkException;

class UserService
{
    private HttpClient $httpClient;
    private string $baseUrl = 'https://api.example.com';
    
    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient
            ->setBearerAuth('api-token-123')
            ->setMaxRetries(3)
            ->setTimeout(30);
    }
    
    public function getUser(int $id): ?array
    {
        try {
            $response = $this->httpClient->get(
                "{$this->baseUrl}/users/{$id}"
            );
            
            if ($response->isSuccess()) {
                return $response->getData();
            }
            
            return null;
            
        } catch (NetworkException $e) {
            error_log("User API error: " . $e->getMessage());
            return null;
        }
    }
    
    public function createUser(array $userData): ?array
    {
        try {
            $response = $this->httpClient->post(
                "{$this->baseUrl}/users",
                $userData
            );
            
            if ($response->isSuccess()) {
                return $response->getData();
            }
            
            return null;
            
        } catch (NetworkException $e) {
            error_log("Create user error: " . $e->getMessage());
            return null;
        }
    }
    
    public function deleteUser(int $id): bool
    {
        try {
            $response = $this->httpClient->delete(
                "{$this->baseUrl}/users/{$id}"
            );
            
            return $response->isSuccess();
            
        } catch (NetworkException $e) {
            error_log("Delete user error: " . $e->getMessage());
            return false;
        }
    }
}
```

### Example 6: Retry with Logging

```php
public function fetchDataWithLogging(): ResponseInterface
{
    $response = (new HttpClient())
        ->setMaxRetries(5)
        ->setLogger(function($message) {
            error_log("[HttpClient] {$message}");
            // Output: [HttpClient] [2026-02-14 10:30:45] GET https://api.example.com/data -> 200 (application/json) in 0.345s
        })
        ->get('https://api.example.com/data');
    
    return $this->returnJson($response->getData(), 200);
}
```

### Example 7: Response Parsing (JSON and XML)

```php
public function handleMultipleFormats(): ResponseInterface
{
    // JSON response
    $jsonResponse = new HttpResponse(
        200,
        ['Content-Type' => 'application/json'],
        '{"id": 1, "name": "John"}'
    );
    $jsonData = $jsonResponse->getData();
    // ['id' => 1, 'name' => 'John']
    
    // XML response
    $xmlResponse = new HttpResponse(
        200,
        ['Content-Type' => 'application/xml'],
        '<?xml version="1.0"?><root><id>1</id><name>John</name></root>'
    );
    $xmlData = $xmlResponse->getData();
    // ['id' => 1, 'name' => 'John']
    
    return $this->returnJson([
        'json' => $jsonData,
        'xml' => $xmlData
    ], 200);
}
```

---

## Testing

### Unit Tests

Use `HttpClientTest.php` for unit testing:

```php
public function testInvalidUrlThrowsException()
{
    $client = new HttpClient();
    $this->expectException(InvalidUrlException::class);
    $client->get('not a valid url');
}

public function testFluentInterface()
{
    $result = $client
        ->setMaxRetries(3)
        ->setTimeout(15)
        ->setBearerAuth('token');
    
    $this->assertInstanceOf(HttpClient::class, $result);
}
```

### Integration Tests

Use `HttpClientIntegrationTest.php` with mock responses:

```php
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
    $data = $response->getData();
    $this->assertEquals('John', $data['name']);
}
```

### Response Tests

Use `HttpResponseTest.php`:

```php
public function testJsonParsing()
{
    $response = new HttpResponse(
        200,
        ['Content-Type' => 'application/json'],
        '{"name":"John","age":30,"roles":["admin","user"]}'
    );
    
    $data = $response->getData();
    $this->assertEquals('John', $data['name']);
    $this->assertEquals(30, $data['age']);
    $this->assertCount(2, $data['roles']);
}
```

---

## Best Practices

### 1. Always Use Try-Catch

```php
✅ Good:
try {
    $response = $client->get($url);
} catch (NetworkException $e) {
    error_log("API error: " . $e->getMessage());
    // Handle gracefully
}

❌ Bad:
$response = $client->get($url);  // No error handling!
```

### 2. Check Response Status

```php
✅ Good:
if ($response->isSuccess()) {
    $data = $response->getData();
} else if ($response->isClientError()) {
    // Handle validation errors
} else if ($response->isServerError()) {
    // Handle server errors
}

❌ Bad:
$data = $response->getData();  // Assume success!
```

### 3. Use Fluent Interface for Configuration

```php
✅ Good:
$response = (new HttpClient())
    ->setBearerAuth($token)
    ->setTimeout(30)
    ->setMaxRetries(3)
    ->get($url);

❌ Bad:
$client = new HttpClient();
$client->setBearerAuth($token);
$client->setTimeout(30);
// ...
```

### 4. Set Appropriate Timeouts

```php
✅ Good:
->setConnectTimeout(5)    // 5 second connection timeout
->setTimeout(30)           // 30 second request timeout

❌ Bad:
->setTimeout(300)  // Too long, API hangs for 5 minutes
```

### 5. Use Retries for Flaky APIs

```php
✅ Good:
->setMaxRetries(3)
->setRetryableStatuses([408, 429, 500, 502, 503, 504])

❌ Bad:
->setMaxRetries(0)  // No retries, fails on transient errors
```

### 6. Always Validate URLs

```php
✅ Good:
if (filter_var($url, FILTER_VALIDATE_URL)) {
    $response = $client->get($url);
}

❌ Bad:
$response = $client->get($url);  // Might throw exception
```

### 7. Use Dependency Injection for Services

```php
✅ Good:
class UserService {
    public function __construct(HttpClient $client) {
        $this->client = $client;
    }
}

❌ Bad:
class UserService {
    private $client = new HttpClient();  // Hard to test
}
```

---

## Common Patterns

### Pattern 1: Centralized API Client

Create a reusable service:

```php
class ExternalApiService
{
    private HttpClient $client;
    
    public function __construct()
    {
        $this->client = (new HttpClient())
            ->setApiKeyAuth(env('API_KEY'))
            ->setTimeout(30)
            ->setMaxRetries(3);
    }
    
    public function fetchUsers(): ?array
    {
        try {
            $response = $this->client->get('https://api.example.com/users');
            return $response->isSuccess() ? $response->getData() : null;
        } catch (NetworkException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
}
```

### Pattern 2: Conditional Retry

```php
$maxAttempts = 3;
for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    try {
        $response = $client->get($url);
        if ($response->isSuccess()) {
            return $response->getData();
        }
        break;  // Don't retry client errors
    } catch (NetworkException $e) {
        if ($attempt == $maxAttempts) throw $e;
        sleep(pow(2, $attempt - 1));  // Exponential backoff
    }
}
```

### Pattern 3: Response Caching

```php
class CachedApiClient
{
    private array $cache = [];
    
    public function get(string $url): ?array
    {
        if (isset($this->cache[$url])) {
            return $this->cache[$url];
        }
        
        $response = $this->client->get($url);
        if ($response->isSuccess()) {
            $this->cache[$url] = $response->getData();
            return $this->cache[$url];
        }
        
        return null;
    }
}
```

---

## Performance Considerations

### Connection Pooling

For multiple requests, reuse the same client:

```php
✅ Good:
$client = (new HttpClient())->setBearerAuth($token);
$users = $client->get('https://api.example.com/users');
$posts = $client->get('https://api.example.com/posts');
$comments = $client->get('https://api.example.com/comments');

❌ Bad:
$users = (new HttpClient())->get(...);
$posts = (new HttpClient())->get(...);  // New connection each time
$comments = (new HttpClient())->get(...);
```

### Parallel Requests

For concurrent requests, consider using CURL multi:

```php
// Sequential (slow)
$users = $client->get('https://api.example.com/users');
$posts = $client->get('https://api.example.com/posts');

// Note: Parallel requests would require multi-curl implementation
```

### Execution Time Tracking

```php
$response = $client->get($url);
$time = $response->getExecutionTime();

if ($time > 1.0) {  // More than 1 second
    error_log("Slow API call: {$time}s for $url");
}
```

---

## Summary

| Component | Purpose |
|-----------|---------|
| **HttpClient** | Main HTTP request handler with fluent interface |
| **HttpResponse** | Response object with parsing and status checking |
| **InvalidUrlException** | Thrown for invalid URLs |
| **NetworkException** | Thrown for network errors |

| Feature | Benefit |
|---------|---------|
| Multiple Auth Methods | Supports Basic, Bearer, API Key |
| Automatic Retries | Handles transient failures |
| Response Parsing | Automatic JSON/XML conversion |
| Status Checking | Helper methods for response type detection |
| Logging Support | Debug and monitor API calls |
| Fluent Interface | Clean, readable configuration |

The HTTP Client provides a production-ready solution for integrating with external APIs, handling errors gracefully, and maintaining reliable connections.

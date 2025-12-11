# AbstractResponse Documentation

## Overview

`AbstractResponse` is an abstract base class that provides the foundation for all HTTP response objects in the Attaquant framework. It implements the `ResponseInterface` and defines the core structure for managing HTTP status codes, headers, and response bodies. This class ensures consistent response handling across the application.

**Location:** `Kernel/AbstractResponse.php`  
**Namespace:** `App\Kernel`  
**Pattern:** Abstract Base Class, Template Method Pattern

## Class Definition

```php
abstract class AbstractResponse implements ResponseInterface
```

## Properties

### Protected Properties

#### `$statusCode` (int)
- **Type:** `int`
- **Description:** HTTP status code for the response
- **Default:** `200` (OK)
- **Scope:** Protected
- **Common Values:**
  - `200` - OK
  - `201` - Created
  - `400` - Bad Request
  - `401` - Unauthorized
  - `404` - Not Found
  - `422` - Unprocessable Entity
  - `500` - Internal Server Error

#### `$headers` (array)
- **Type:** `array`
- **Description:** HTTP response headers as key-value pairs
- **Default:** `[]` (empty array)
- **Scope:** Protected
- **Format:** `['Header-Name' => 'Header Value']`
- **Examples:**
  - `['Content-Type' => 'application/json']`
  - `['Authorization' => 'Bearer token']`
  - `['Cache-Control' => 'no-cache']`

#### `$body` (string)
- **Type:** `string`
- **Description:** Response body content (typically JSON or HTML)
- **Default:** `''` (empty string)
- **Scope:** Protected
- **Content:** Response data to be sent to client

## Methods

### Abstract Methods

#### `setBody(mixed $content): void`

```php
abstract public function setBody(mixed $content): void;
```

**Description:**  
Abstract method that must be implemented by concrete response classes. Defines how the response body is set and formatted.

**Parameters:**
- `$content` (mixed) - Content to set as response body (can be array, string, object, etc.)

**Return Type:** void

**Implementation Requirements:**
- Must convert the content to appropriate format (JSON, HTML, etc.)
- Must set the `$body` property
- May set appropriate Content-Type header

**Example Implementation:**
```php
public function setBody(mixed $content): void
{
    $this->body = json_encode($content);
    $this->setHeader('Content-Type', 'application/json');
}
```

---

#### `sendReponse(): void`

```php
abstract public function sendReponse(): void;
```

**Description:**  
Abstract method that must be implemented by concrete response classes. Handles response-specific logic before sending.

**Parameters:** None

**Return Type:** void

**Implementation Requirements:**
- May perform pre-send operations
- May set additional headers
- May validate response state
- Called before headers and body are sent

**Note:** Method name has a typo (`sendReponse` instead of `sendResponse`). This is maintained for backward compatibility.

**Example Implementation:**
```php
public function sendReponse(): void
{
    // Perform any pre-send operations
    // Set additional headers if needed
}
```

---

### Public Methods

#### `setStatusCode(int $code): void`

```php
public function setStatusCode(int $code): void
```

**Description:**  
Sets the HTTP status code for the response.

**Parameters:**
- `$code` (int) - HTTP status code (100-599)

**Return Type:** void

**Behavior:**
- Updates the `$statusCode` property
- Does not validate the code (any integer is accepted)
- Can be called multiple times (last value wins)

**Common Status Codes:**

| Code | Meaning | Use Case |
|------|---------|----------|
| 200 | OK | Successful GET, PUT, PATCH |
| 201 | Created | Successful POST creating resource |
| 204 | No Content | Successful DELETE |
| 400 | Bad Request | Invalid request data |
| 401 | Unauthorized | Missing/invalid authentication |
| 403 | Forbidden | Authenticated but not authorized |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation error |
| 500 | Internal Server Error | Server error |
| 503 | Service Unavailable | Server temporarily unavailable |

**Example:**
```php
$response = new JsonResponse();
$response->setStatusCode(201);  // Created
$response->setBody(['id' => 1, 'name' => 'John']);
```

---

#### `setHeader(string $name, string $value): void`

```php
public function setHeader(string $name, string $value): void
```

**Description:**  
Sets an HTTP response header.

**Parameters:**
- `$name` (string) - Header name (e.g., "Content-Type", "Authorization")
- `$value` (string) - Header value

**Return Type:** void

**Behavior:**
- Stores header in `$headers` array
- Overwrites existing header with same name
- Headers are sent in HTTP format: `Header-Name: Header Value`
- Can be called multiple times for different headers

**HTTP Header Format:**
```
Header-Name: Header Value
```

**Common Headers:**

| Header | Value | Purpose |
|--------|-------|---------|
| Content-Type | application/json | Specify response format |
| Content-Length | 1234 | Specify body size |
| Cache-Control | no-cache | Control caching |
| Authorization | Bearer token | Authentication |
| Access-Control-Allow-Origin | * | CORS support |
| X-Custom-Header | value | Custom headers |

**Example:**
```php
$response = new JsonResponse();
$response->setHeader('Content-Type', 'application/json');
$response->setHeader('Cache-Control', 'no-cache');
$response->setHeader('X-API-Version', '1.0');
```

---

#### `send(): void`

```php
public function send(): void
```

**Description:**  
Sends the complete HTTP response to the client. This is the main method that orchestrates sending status code, headers, and body.

**Parameters:** None

**Return Type:** void

**Behavior:**

1. **Call sendReponse():**
   - Executes the abstract method implementation
   - Allows subclasses to perform pre-send operations

2. **Send Status Code:**
   - Uses `http_response_code()` to set HTTP status
   - Must be called before any output

3. **Send Headers:**
   - Iterates through `$headers` array
   - Sends each header using `header()` function
   - Format: `Header-Name: Header Value`

4. **Send Body:**
   - Echoes the `$body` content
   - Sent after all headers

**Execution Order:**
```
send() called
    ↓
sendReponse() (abstract implementation)
    ↓
http_response_code($statusCode)
    ↓
foreach headers: header("Name: Value")
    ↓
echo $body
    ↓
Response sent to client
```

**Important Notes:**
- Must be called only once per request
- Cannot be called after output has been sent
- Headers must be sent before body
- No output should occur before calling `send()`

**Example:**
```php
$response = new JsonResponse();
$response->setStatusCode(200);
$response->setHeader('Content-Type', 'application/json');
$response->setBody(['status' => 'success']);
$response->send();
```

---

## Implementation Pattern

AbstractResponse uses the **Template Method Pattern** where:
- `send()` defines the overall algorithm
- Subclasses implement `setBody()` and `sendReponse()` for specific behavior

### Creating a Concrete Response Class

```php
class CustomResponse extends AbstractResponse
{
    public function setBody(mixed $content): void
    {
        // Convert content to appropriate format
        $this->body = (string) $content;
        $this->setHeader('Content-Type', 'text/plain');
    }

    public function sendReponse(): void
    {
        // Perform any pre-send operations
        // Set additional headers if needed
    }
}
```

## Response Interface

AbstractResponse implements `ResponseInterface`:

```php
interface ResponseInterface
{
    public function setStatusCode(int $code): void;
    public function setHeader(string $name, string $value): void;
    public function send(): void;
    public function setBody(mixed $content): void;
    public function sendReponse(): void;
}
```

## Built-in Response Classes

The framework provides several concrete implementations:

### JsonResponse
Sends JSON-formatted responses.

```php
$response = new JsonResponse();
$response->setStatusCode(200);
$response->setBody(['key' => 'value']);
$response->send();
```

### ErrorResponse
Sends error responses with 500 status code.

```php
$response = new ErrorResponse();
$response->send();
```

### ClientErrorResponse
Sends client error responses (4xx status codes).

```php
$response = new ClientErrorResponse(404);
$response->send();
```

## Usage Examples

### Basic Response

```php
use App\Services\Responses\JsonResponse;

$response = new JsonResponse();
$response->setStatusCode(200);
$response->setBody(['message' => 'Success']);
$response->send();
```

### Response with Custom Headers

```php
$response = new JsonResponse();
$response->setStatusCode(200);
$response->setHeader('Content-Type', 'application/json');
$response->setHeader('X-API-Version', '1.0');
$response->setHeader('Cache-Control', 'no-cache');
$response->setBody(['data' => 'value']);
$response->send();
```

### Error Response

```php
$response = new ErrorResponse(500);
$response->setHeader('Content-Type', 'application/json');
$response->send();
```

### Created Resource Response

```php
$response = new JsonResponse();
$response->setStatusCode(201);  // Created
$response->setHeader('Location', '/api/users/123');
$response->setBody(['id' => 123, 'name' => 'John']);
$response->send();
```

### In a Controller

```php
public function index(): ResponseInterface
{
    $data = $this->model->getAll();
    
    $response = new JsonResponse();
    $response->setStatusCode(200);
    $response->setBody($data);
    
    return $response;
}
```

## HTTP Response Structure

A complete HTTP response consists of:

```
HTTP/1.1 200 OK
Content-Type: application/json
Content-Length: 27
Cache-Control: no-cache

{"status":"success","id":1}
```

**Components:**
1. **Status Line:** `HTTP/1.1 200 OK`
2. **Headers:** Key-value pairs
3. **Blank Line:** Separates headers from body
4. **Body:** Response content

## Best Practices

### 1. Always Set Appropriate Status Code

```php
// Good
$response->setStatusCode(201);  // Created

// Avoid
$response->setStatusCode(200);  // Wrong for creation
```

### 2. Set Content-Type Header

```php
// Good
$response->setHeader('Content-Type', 'application/json');

// Avoid
// Missing Content-Type header
```

### 3. Use Appropriate Response Classes

```php
// Good
$response = new JsonResponse();

// Avoid
$response = new AbstractResponse();  // Can't instantiate abstract class
```

### 4. Set Headers Before Sending

```php
// Good
$response->setHeader('X-Custom', 'value');
$response->send();

// Avoid
$response->send();
$response->setHeader('X-Custom', 'value');  // Too late
```

### 5. Return ResponseInterface from Controllers

```php
// Good
public function index(): ResponseInterface
{
    return new JsonResponse();
}

// Avoid
public function index()
{
    $response = new JsonResponse();
    $response->send();  // Don't send in controller
}
```

## Common Patterns

### Success Response

```php
$response = new JsonResponse();
$response->setStatusCode(200);
$response->setBody(['status' => 'success', 'data' => $data]);
$response->send();
```

### Created Response

```php
$response = new JsonResponse();
$response->setStatusCode(201);
$response->setHeader('Location', '/api/resource/' . $id);
$response->setBody(['id' => $id, 'data' => $data]);
$response->send();
```

### Error Response

```php
$response = new ErrorResponse(500);
$response->setHeader('Content-Type', 'application/json');
$response->send();
```

### Validation Error Response

```php
$response = new ClientErrorResponse(422);
$response->setBody(['errors' => $validationErrors]);
$response->send();
```

## Error Handling

### Headers Already Sent

**Error:** "Headers already sent"

**Cause:** Output sent before `send()` is called

**Solution:**
```php
// Ensure no output before send()
// No echo, print, or var_dump before send()
$response->send();
```

### Invalid Status Code

**Issue:** Invalid HTTP status codes

**Solution:**
```php
// Use valid HTTP status codes (100-599)
$response->setStatusCode(200);  // Valid
$response->setStatusCode(999);  // Invalid but accepted
```

## Performance Considerations

1. **Header Overhead:** Each header adds to response size
2. **Body Size:** Large bodies impact bandwidth
3. **Compression:** Consider gzip compression for large responses
4. **Caching:** Use appropriate Cache-Control headers

## Testing

### Unit Test Example

```php
use PHPUnit\Framework\TestCase;
use App\Services\Responses\JsonResponse;

class AbstractResponseTest extends TestCase
{
    public function testSetStatusCode()
    {
        $response = new JsonResponse();
        $response->setStatusCode(201);
        $this->assertEquals(201, $response->statusCode);
    }

    public function testSetHeader()
    {
        $response = new JsonResponse();
        $response->setHeader('X-Custom', 'value');
        $this->assertEquals('value', $response->headers['X-Custom']);
    }

    public function testSetBody()
    {
        $response = new JsonResponse();
        $response->setBody(['key' => 'value']);
        $this->assertNotEmpty($response->body);
    }
}
```

## Related Classes

- **ResponseInterface** (`App\Kernel\Interfaces\ResponseInterface`)
  - Interface that AbstractResponse implements

- **JsonResponse** (`App\Services\Responses\JsonResponse`)
  - Concrete implementation for JSON responses

- **ErrorResponse** (`App\Services\Responses\ErrorResponse`)
  - Concrete implementation for error responses

- **ClientErrorResponse** (`App\Services\Responses\ClientErrorResponse`)
  - Concrete implementation for client error responses

## Related Documentation

- [RouterObject Documentation](./RouterObject.md) - Returns responses from controllers
- [RequestObject Documentation](./RequestObject.md) - Request data for processing

## Changelog

### Version 1.0
- Initial implementation
- Abstract base class for responses
- Status code management
- Header management
- Response sending

## Future Enhancements

- [ ] Response compression support
- [ ] Automatic Content-Length calculation
- [ ] CORS header helpers

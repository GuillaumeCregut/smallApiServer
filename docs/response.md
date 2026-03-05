# Response System Documentation

## Overview

The Response system is a comprehensive framework for handling HTTP responses in the smallAPIServer application. It provides an abstraction layer for managing response status codes, headers, and body content across different response types (JSON, HTML, errors).

All response classes implement the `ResponseInterface` contract and extend the `AbstractResponse` base class, ensuring consistent behavior throughout the application.

---

## ResponseInterface

### Description

`ResponseInterface` defines the contract that all response classes must follow. It ensures a consistent API for managing HTTP responses.

### Location
`App\Kernel\Interfaces\ResponseInterface`

### Methods

```php
public function setStatusCode(int $code): void;
public function setHeader(string $name, string $value): void;
public function setBody(string $content): void;
public function send(): string;
```

| Method | Purpose |
|--------|---------|
| `setStatusCode(int $code)` | Set the HTTP status code (e.g., 200, 404, 500) |
| `setHeader(string $name, string $value)` | Set an HTTP header (e.g., 'Content-Type', 'application/json') |
| `setBody(string $content)` | Set the response body content |
| `send()` | Send the response with status code, headers, and body |

---

## AbstractResponse

### Description

`AbstractResponse` is the base class for all response types. It implements the core functionality for managing response properties (status code, headers, body) and provides an extension point for subclasses to customize response behavior.

### Location
`App\Kernel\Responses\AbstractResponse`

### Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$statusCode` | `int` | `200` | The HTTP status code to be sent |
| `$headers` | `array` | `[]` | HTTP headers as key-value pairs |
| `$body` | `string` | `''` | The response body content |

### Abstract Methods (Must be implemented by subclasses)

```php
abstract public function setBody(mixed $content): void;
abstract public function sendReponse(): void;
abstract protected function displayDump(): void;
```

### Key Public Methods

#### `setStatusCode(int $code): void`
Sets the HTTP status code for the response.

```php
$response->setStatusCode(201);
```

#### `setHeader(string $name, string $value): void`
Sets a single HTTP header. Headers follow HTTP format: `Header-Name: Header Value`

```php
$response->setHeader('Content-Type', 'application/json');
$response->setHeader('X-Custom-Header', 'value');
```

#### `send(): string`
Sends the complete response including status code, headers, and body. It also handles debug mode display if configured.

```php
$response->send();
```

#### `getBody(): string`
Returns the current response body (useful for testing).

```php
$body = $response->getBody();
```

#### `getHeaders(): array`
Returns all set headers as an associative array (useful for testing).

```php
$headers = $response->getHeaders();
```

#### `getStatusCode(): int`
Returns the current HTTP status code (useful for testing).

```php
$code = $response->getStatusCode();
```

### Debug Mode

If debug mode is enabled in environment configuration, the response will display additional debugging information via the `displayDump()` method implemented by subclasses.

---

## Cookies

### Description

All response classes support setting HTTP cookies through the `setCookie()` method inherited from `AbstractResponse`. Cookies are automatically sent as HTTP Set-Cookie headers when the response is sent. The cookie system provides secure options including HTTP-only access and secure transmission flags.

### Cookie Constants

The `Cookie` class provides security flag constants:

**Location:** `App\Kernel\Responses\Cookie`

```php
class Cookie
{
    public const COOKIE_SECURE = 1;      // Send only over HTTPS
    public const COOKIE_HTTPONLY = 2;    // Not accessible from JavaScript
}
```

| Constant | Value | Purpose |
|----------|-------|---------|
| `COOKIE_SECURE` | 1 | Only transmit cookie over HTTPS connections |
| `COOKIE_HTTPONLY` | 2 | Prevent browser JavaScript from accessing cookie value |

### setCookie() Method

#### Signature

```php
public function setCookie(string $name, string $value, ?array $params = []): self
```

**Parameters:**
- `$name` (string): Cookie name (identifier)
- `$value` (string): Cookie value (data to store)
- `$params` (array, optional): Cookie options (see below)

**Returns:** `self` - Returns the response object for method chaining

#### Cookie Parameters

The `$params` array supports the following options:

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `domain` | string | Domain where cookie is valid | `'example.com'` |
| `path` | string | URL path where cookie is valid | `'/'`, `'/api'` |
| `expire` | int | Expiration time as Unix timestamp | `time() + 3600` |
| `sameSite` | string | SameSite policy for CSRF protection | `'Strict'`, `'Lax'`, `'None'` |
| `security` | int | Security flags (bitwise OR combination) | `Cookie::COOKIE_HTTPONLY \| Cookie::COOKIE_SECURE` |

### Cookie Parameter Details

#### Domain

Specifies the domain(s) for which the cookie is valid.

```php
// Cookie available on example.com and subdomains
$response->setCookie('session', 'abc123', [
    'domain' => '.example.com'
]);

// Cookie available on current domain only
$response->setCookie('session', 'abc123', [
    'domain' => ''  // Empty string = current domain
]);
```

#### Path

Specifies the URL path for which the cookie is valid.

```php
// Cookie available at all paths
$response->setCookie('session', 'abc123', [
    'path' => '/'
]);

// Cookie available only under /admin
$response->setCookie('admin_token', 'xyz789', [
    'path' => '/admin'
]);
```

#### Expire

Specifies when the cookie expires. Value is a Unix timestamp.

```php
// Session cookie (expires when browser closes)
$response->setCookie('session', 'abc123', [
    'expire' => 0
]);

// Expires in 1 hour
$response->setCookie('token', 'xyz789', [
    'expire' => time() + 3600
]);

// Expires in 30 days
$response->setCookie('remember', 'user123', [
    'expire' => time() + (30 * 24 * 60 * 60)
]);
```

#### SameSite

Specifies when the cookie should be sent in cross-site requests, providing CSRF protection.

**Values:**
- `'Strict'`: Cookie sent only when request originates from same site
- `'Lax'`: Cookie sent with same-site requests and top-level navigations
- `'None'`: Cookie sent with all requests; requires `COOKIE_SECURE` flag

```php
// Strict CSRF protection
$response->setCookie('session', 'abc123', [
    'sameSite' => 'Strict'
]);

// Lax CSRF protection (balanced)
$response->setCookie('session', 'abc123', [
    'sameSite' => 'Lax'
]);

// Allow cross-site with HTTPS only
$response->setCookie('tracking', 'id123', [
    'sameSite' => 'None',
    'security' => Cookie::COOKIE_SECURE
]);
```

#### Security

Specifies security flags using bitwise OR combination of constants.

```php
// HTTP-only only
$response->setCookie('session', 'abc123', [
    'security' => Cookie::COOKIE_HTTPONLY
]);

// HTTPS only
$response->setCookie('session', 'abc123', [
    'security' => Cookie::COOKIE_SECURE
]);

// Both HTTP-only AND HTTPS (recommended for sensitive data)
$response->setCookie('auth', 'token123', [
    'security' => Cookie::COOKIE_HTTPONLY | Cookie::COOKIE_SECURE
]);

// No security flags
$response->setCookie('public', 'data', [
    'security' => 0
]);
```

### Usage Examples

#### Example 1: Session Cookie (HomeController getDatas)

This example shows how to set a secure session cookie in the HomeController's getDatas method:

```php
class HomeController extends AbstractController
{
    public function getDatas(): ResponseInterface
    {
        // Get data from database
        $datas = $this->getDataFromDB();
        
        // Create JSON response
        $response = $this->returnJson($datas, 200);
        
        // Set secure session cookie
        $options = [
            'security' => Cookie::COOKIE_HTTPONLY | Cookie::COOKIE_SECURE
        ];
        $response->setCookie('test', 'toto', $options);
        
        return $response;
    }
}
```

**Result:**
- Cookie name: `test`
- Cookie value: `toto`
- Security: HTTPS only, not accessible from JavaScript
- Expires: When browser closes (session cookie)

#### Example 2: Remember Me Cookie

```php
public function login(string $username, string $password): ResponseInterface
{
    if ($this->authenticate($username, $password)) {
        $response = new JsonResponse(200);
        $response->setBody(['success' => true]);
        
        // Set 30-day remember me cookie
        $rememberToken = bin2hex(random_bytes(32));
        $response->setCookie('remember_me', $rememberToken, [
            'expire' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'security' => Cookie::COOKIE_HTTPONLY | Cookie::COOKIE_SECURE,
            'sameSite' => 'Lax'
        ]);
        
        return $response;
    }
    
    return new ClientErrorResponse(401);
}
```

#### Example 3: API Token Cookie

```php
public function generateApiToken(): ResponseInterface
{
    $user = $this->request->getUser();
    $token = $this->generateJwtToken($user->getId());
    
    $response = new JsonResponse(200);
    $response->setBody(['token' => $token]);
    
    // Set secure API token cookie for AJAX requests
    $response->setCookie('api_token', $token, [
        'path' => '/api',
        'expire' => time() + 3600,  // 1 hour validity
        'security' => Cookie::COOKIE_HTTPONLY | Cookie::COOKIE_SECURE,
        'sameSite' => 'Strict',
        'domain' => ''  // Current domain
    ]);
    
    return $response;
}
```

#### Example 4: Multi-Domain Cookie

```php
public function setSharedCookie(): ResponseInterface
{
    $response = new JsonResponse(200);
    
    // Set cookie available across subdomains
    $response->setCookie('shared_session', 'abcd1234', [
        'domain' => '.example.com',  // Available on app.example.com, api.example.com, etc.
        'path' => '/',
        'expire' => time() + 86400,  // 24 hours
        'security' => Cookie::COOKIE_HTTPONLY | Cookie::COOKIE_SECURE,
        'sameSite' => 'Lax'
    ]);
    
    return $response;
}
```

### Method Chaining

The `setCookie()` method returns `self`, allowing fluent interface chaining:

```php
$response = new JsonResponse(200);
$response
    ->setBody(['data' => 'value'])
    ->setCookie('session', 'abc123', ['security' => Cookie::COOKIE_HTTPONLY])
    ->setCookie('tracking', 'def456', ['path' => '/admin'])
    ->send();
```

### Cookie Lifecycle

```
1. setCookie() called on response object
   ↓
2. Cookie added to response's internal cookies array
   ↓
3. send() method called
   ↓
4. Status code sent
   ↓
5. Headers sent
   ↓
6. Cookies sent via HTTP Set-Cookie headers
   ↓
7. Body sent
   ↓
8. Cookies stored in client browser
```

### How setcookie() Works Internally

When `send()` is called, the response invokes `sendCookies()` which:

1. Iterates through stored cookies
2. Extracts cookie value and parameters
3. Sets defaults for omitted parameters
4. Handles security flags (bitwise AND operations)
5. Calls PHP's `setcookie()` with appropriate options
6. Sends HTTP `Set-Cookie` header

### Security Best Practices for Cookies

#### 1. Always Use HTTPS in Production

```php
✅ Good (production):
'security' => Cookie::COOKIE_HTTPONLY | Cookie::COOKIE_SECURE

❌ Bad (development only):
'security' => 0  // No security flags!
```

#### 2. Set HTTP-Only Flag for Security-Sensitive Cookies

```php
✅ Good - Token not accessible to JavaScript:
'security' => Cookie::COOKIE_HTTPONLY | Cookie::COOKIE_SECURE

❌ Bad - Cookie accessible to XSS attacks:
'security' => Cookie::COOKIE_SECURE  // Missing HTTPONLY
```

#### 3. Use Appropriate SameSite Policy

```php
✅ Good - CSRF protection:
'sameSite' => 'Strict'  // Strictest protection
'sameSite' => 'Lax'     // Balanced approach

❌ Bad - No CSRF protection:
// No sameSite specified
```

#### 4. Set Short Expiration for Security Tokens

```php
✅ Good - Tokens expire quickly:
'expire' => time() + 3600  // 1 hour

❌ Bad - Tokens valid too long:
'expire' => time() + (365 * 24 * 60 * 60)  // 1 year!
```

#### 5. Use Specific Paths for API Cookies

```php
✅ Good - Limited scope:
'path' => '/api'  // Only sent with /api requests

❌ Bad - Too broad:
'path' => '/'  // Sent with all requests
```

---

## JsonResponse

### Description

`JsonResponse` is used to send JSON-encoded responses. It automatically sets the appropriate headers and encodes content as JSON.

### Location
`App\Kernel\Responses\JsonResponse`

### Constructor

```php
public function __construct(int $statusCode = 200)
```

- **Default status code**: `200 OK`
- **Default content type**: `application/json`

### Methods

#### `setBody(mixed $content): void`
Accepts any content (array, object, string) and encodes it to JSON.

```php
$response = new JsonResponse();
$response->setBody(['user' => 'John', 'id' => 123]);
// Body: {"user":"John","id":123}
```

### Supported Status Codes

The class includes predefined status messages for common success codes:

| Code | Message |
|------|---------|
| 200 | HTTP/1.1 200 OK |
| 201 | HTTP/1.1 201 Created |
| 204 | HTTP/1.1 204 No Content |

### Usage Example

```php
$response = new JsonResponse(201);
$response->setBody([
    'id' => 5,
    'username' => 'john_doe',
    'email' => 'john@example.com'
]);
$response->send();
```

### Test Case

```php
$response = new JsonResponse();
$datas = ['test'];
$response->setBody($datas);
$body = $response->getBody();
// $body contains valid JSON: ["test"]
// Status code: 200
// Content-Type: application/json
```

---

## ErrorResponse

### Description

`ErrorResponse` is used to handle **server-side errors** (HTTP 5xx status codes). It specifically handles `500 Internal Server Error` and can display detailed error information in debug mode.

### Location
`App\Kernel\Responses\ErrorResponse`

### Constructor

```php
public function __construct(int $statusCode = 500, ?bool $debug = false, ?Exception $e = null)
```

**Parameters:**
- `$statusCode` (int, default: 500) - Always set to 500 regardless of input
- `$debug` (bool, default: false) - Enable debug information display
- `$e` (Exception, default: null) - Exception object to extract information from

**Important**: The status code is always forced to 500, even if a different code is provided.

### Debug Mode Behavior

When debug mode is **enabled** (`$debug = true`) and an exception is provided:
- Content-Type is set to `application/json`
- Response includes detailed exception information:
  - `message`: Exception message
  - `code`: Exception code
  - `file`: File where exception occurred
  - `line`: Line number of exception
  - `body`: Standard error message

```json
{
    "message": "Database connection failed",
    "code": 1,
    "file": "/app/Kernel/Database.php",
    "line": 45,
    "body": "500 - Internal Server Error"
}
```

When debug mode is **disabled** or no exception provided:
- Content-Type is set to `text/plain`
- Response body contains only: `500 - Internal Server Error`

### Usage Examples

#### Basic Error Response
```php
$response = new ErrorResponse();
$response->send();
// Returns: 500 - Internal Server Error
// Content-Type: text/plain
```

#### Error Response with Exception (Debug Mode)
```php
try {
    // Some operation that throws an exception
} catch (Exception $e) {
    $response = new ErrorResponse(500, true, $e);
    $response->send();
}
// Returns JSON with detailed exception information
// Content-Type: application/json
```

#### Error Response with Exception (Production Mode)
```php
try {
    // Some operation that throws an exception
} catch (Exception $e) {
    $response = new ErrorResponse(500, false, $e);
    $response->send();
}
// Returns: 500 - Internal Server Error
// Content-Type: text/plain
```

### Test Cases

```php
// Test 1: With exception in debug mode
$exception = new Exception('Sample Message', 20);
$response = new ErrorResponse(500, true, $exception);
// Returns JSON with: message, code, file, line, body fields
// Content-Type: application/json
// Status code: 500

// Test 2: With exception in production mode
$response = new ErrorResponse(500, false, $exception);
// Returns: 500 - Internal Server Error (plain text)
// Content-Type: text/plain
// Status code: 500

// Test 3: Status code override attempt
$response = new ErrorResponse(200, false, $exception);
// Status code is always 500, not 200
```

---

## ClientErrorResponse

### Description

`ClientErrorResponse` is used to handle **client-side errors** (HTTP 4xx status codes). It provides comprehensive support for HTTP 4xx error codes and attempts to auto-detect JSON content in response bodies.

### Location
`App\Kernel\Responses\ClientErrorResponse`

### Constructor

```php
public function __construct(int $statusCode = 404)
```

**Parameters:**
- `$statusCode` (int, default: 404) - The HTTP 4xx status code

If an invalid 4xx code is provided, it defaults to `400 Bad Request`.

### Supported HTTP 4xx Status Codes

The class supports comprehensive HTTP client error codes:

| Code | Message | Code | Message |
|------|---------|------|---------|
| 400 | Bad Request | 421 | Misdirected Request |
| 401 | Unauthorized | 422 | Unprocessable Entity |
| 402 | Payment Required | 423 | Locked |
| 403 | Forbidden | 424 | Failed Dependency |
| 404 | Not Found | 425 | Too Early |
| 405 | Method Not Allowed | 426 | Upgrade Required |
| 406 | Not Acceptable | 428 | Precondition Required |
| 407 | Proxy Authentication Required | 429 | Too Many Requests |
| 408 | Request Timeout | 431 | Request Header Fields Too Large |
| 409 | Conflict | 451 | Unavailable For Legal Reasons |
| 410 | Gone | | |
| 411 | Length Required | | |
| 412 | Precondition Failed | | |
| 413 | Content Too Large | | |
| 414 | URI Too Long | | |
| 415 | Unsupported Media Type | | |
| 416 | Range Not Satisfiable | | |
| 417 | Expectation Failed | | |
| 418 | I'm a teapot | | |

### Methods

#### `setBody(mixed $content): void`
Sets the response body and **auto-detects JSON content**.

The method attempts to decode the content as JSON. If successful, it automatically changes the Content-Type header to `application/json`. Otherwise, it remains `text/plain`.

```php
$response = new ClientErrorResponse(400);

// Plain text body
$response->setBody('Invalid request format');
// Content-Type: text/plain

// JSON body
$response->setBody(json_encode(['error' => 'Invalid email']));
// Content-Type: application/json (auto-detected)
```

### Usage Examples

#### Basic 404 Error
```php
$response = new ClientErrorResponse(404);
$response->send();
// Returns: 404 - Page not found
// Status Code: 404
// Content-Type: text/plain
```

#### 400 Bad Request
```php
$response = new ClientErrorResponse(400);
$response->send();
// Returns: 400 - Bad Request
```

#### Invalid Status Code (Falls back to 400)
```php
$response = new ClientErrorResponse(467); // Invalid code
$response->send();
// Falls back to 400
// Returns: 400 - Bad Request
```

#### JSON Error Response
```php
$response = new ClientErrorResponse(422);
$response->setBody(json_encode([
    'errors' => [
        'email' => 'Email already exists',
        'username' => 'Username too short'
    ]
]));
$response->send();
// Returns JSON data
// Content-Type: application/json (auto-detected)
```

#### Validation Errors as JSON
```php
$response = new ClientErrorResponse(400);
$errors = ['field' => 'Field is required', 'value' => 'Invalid value'];
$response->setBody(json_encode($errors));
// Content-Type automatically set to application/json
```

### Test Cases

```php
// Test 1: Basic 400 error
$response = new ClientErrorResponse(400);
// Body: 400 - Bad Request
// Status: 400
// Content-Type: text/plain

// Test 2: Invalid status code defaults to 400
$response = new ClientErrorResponse(467);
// Falls back to status 400

// Test 3: JSON body auto-detection
$response = new ClientErrorResponse(400);
$response->setBody(json_encode(['test', 'test2']));
// Content-Type automatically set to application/json
```

---

## Complete Usage Example

Here's a complete example showing how to use different response types in a controller:

```php
<?php

namespace App\Controllers;

use App\Kernel\Responses\JsonResponse;
use App\Kernel\Responses\ErrorResponse;
use App\Kernel\Responses\ClientErrorResponse;

class UserController
{
    public function getUser($id)
    {
        try {
            $user = $this->userRepository->find($id);
            
            if (!$user) {
                // Client error: user not found
                $response = new ClientErrorResponse(404);
                return $response->send();
            }
            
            // Success: return user as JSON
            $response = new JsonResponse(200);
            $response->setBody($user);
            return $response->send();
            
        } catch (Exception $e) {
            // Server error: handle exception
            $debug = $_ENV['DEBUG_MODE'] ?? false;
            $response = new ErrorResponse(500, $debug, $e);
            return $response->send();
        }
    }
    
    public function createUser($data)
    {
        try {
            // Validate data
            if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $response = new ClientErrorResponse(422);
                $response->setBody(json_encode([
                    'errors' => ['email' => 'Invalid email format']
                ]));
                return $response->send();
            }
            
            // Create user
            $user = $this->userRepository->create($data);
            
            // Return created user
            $response = new JsonResponse(201);
            $response->setBody($user);
            return $response->send();
            
        } catch (Exception $e) {
            $response = new ErrorResponse(500, false, $e);
            return $response->send();
        }
    }
}
```

---

## Response Flow Diagram

```
┌─────────────────────────────────────┐
│   ResponseInterface (Contract)      │
└─────────────────┬───────────────────┘
                  │
                  ▼
┌─────────────────────────────────────┐
│   AbstractResponse (Base Logic)     │
│  - statusCode (default: 200)        │
│  - headers                          │
│  - body                             │
│  - send() method                    │
└────┬────────────────┬───────────┬───┘
     │                │           │
     ▼                ▼           ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────────┐
│ JsonResponse │ │ErrorResponse │ │ClientErrorResponse│
│ (Success)    │ │(5xx errors)  │ │(4xx errors)      │
│ - 2xx codes  │ │- 500 code    │ │- 4xx codes       │
│ - JSON body  │ │- Debug mode  │ │- Auto JSON detect│
└──────────────┘ └──────────────┘ └──────────────────┘
```

---

## Testing

The response classes include comprehensive unit tests. Here are the testing patterns:

### JsonResponse Testing
- Verify JSON encoding of response body
- Check Content-Type header is set to `application/json`
- Validate status code is 200 by default

### ErrorResponse Testing
- Test debug mode returns JSON with exception details
- Test production mode returns plain text
- Verify status code is always forced to 500

### ClientErrorResponse Testing
- Test various 4xx status codes
- Verify JSON auto-detection in setBody()
- Test invalid status codes fall back to 400

---

## Summary

| Class | Purpose | Default Status | Content Types |
|-------|---------|------------------|---|
| `JsonResponse` | Success responses | 200 | `application/json` |
| `ErrorResponse` | Server errors | 500 | `text/plain` or `application/json` (debug) |
| `ClientErrorResponse` | Client errors | 404 | `text/plain` or `application/json` (auto-detected) |

All response classes provide a consistent interface for setting status codes, headers, and body content, with each class optimized for its specific use case.

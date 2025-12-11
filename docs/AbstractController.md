# AbstractController Documentation

## Overview

`AbstractController` is an abstract base class that provides the foundation for all controllers in the SmallMVC framework. It encapsulates common controller functionality including request handling, authentication, error responses, and middleware integration. All application controllers should extend this class to inherit these capabilities.

**Location:** `Kernel/AbstractController.php`  
**Namespace:** `App\Kernel`  
**Pattern:** Abstract Base Class, Template Method Pattern

## Class Definition

```php
abstract class AbstractController
```

## Properties

### Protected Properties

#### `$connector` (ConnectorInterface)
- **Type:** `ConnectorInterface`
- **Description:** Database connector for performing database operations
- **Scope:** Protected
- **Initialized:** By subclasses (not in AbstractController)
- **Purpose:** Provides database access to models
- **Usage:** Pass to models for data operations

**Example:**
```php
public function __construct(AuthenticationInterface $authMiddleware)
{
    parent::__construct($authMiddleware);
    $this->connector = new Connector();
}
```

#### `$request` (RequestObject)
- **Type:** `RequestObject`
- **Description:** Singleton instance of RequestObject containing all HTTP request data
- **Scope:** Protected
- **Initialized:** In AbstractController constructor
- **Purpose:** Access request data (parameters, headers, files, sessions)
- **Usage:** `$this->request->getAllDatas()`, `$this->request->getMethod()`, etc.

#### `$authMiddleware` (AuthenticationInterface)
- **Type:** `AuthenticationInterface`
- **Description:** Authentication middleware for handling authentication logic
- **Scope:** Protected (promoted property)
- **Initialized:** Via constructor parameter
- **Purpose:** Perform authentication checks
- **Usage:** Injected by RouterObject

## Methods

### Constructor

```php
public function __construct(protected AuthenticationInterface $authMiddleware)
```

**Description:**  
Initializes the AbstractController with authentication middleware and request object.

**Parameters:**
- `$authMiddleware` (AuthenticationInterface) - Authentication middleware instance (promoted property)

**Return Type:** void

**Behavior:**
- Stores the authentication middleware as a protected property
- Retrieves the singleton RequestObject instance
- Makes request data available to all controller methods

**Property Promotion:**
- Uses PHP 8 constructor property promotion
- `$authMiddleware` automatically becomes a protected property

**Example:**
```php
public function __construct(AuthenticationInterface $authMiddleware)
{
    parent::__construct($authMiddleware);
    $this->connector = new Connector();
}
```

**Initialization Flow:**
```
RouterObject instantiates controller
    ↓
Passes AuthBearerMiddleware to constructor
    ↓
AbstractController constructor called
    ├─ Stores $authMiddleware
    └─ Gets RequestObject singleton
    ↓
Subclass constructor called
    ├─ Calls parent::__construct()
    └─ Initializes additional properties
```

---

### Protected Methods

#### `returnError(int $error): ResponseInterface`

```php
protected function returnError(int $error): ResponseInterface
```

**Description:**  
Creates and returns a ClientErrorResponse with the specified HTTP error code.

**Parameters:**
- `$error` (int) - HTTP error status code

**Return Type:** `ResponseInterface`

**Return Values:**
- ClientErrorResponse object with the specified error code

**Behavior:**
- Creates a new ClientErrorResponse instance
- Sets the error code
- Returns the response object
- Does not send the response (allows further modification)

**Common Error Codes:**

| Code | Meaning | Use Case |
|------|---------|----------|
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Missing/invalid authentication |
| 403 | Forbidden | Authenticated but not authorized |
| 404 | Not Found | Resource doesn't exist |
| 405 | Method Not Allowed | HTTP method not supported |
| 422 | Unprocessable Entity | Validation error |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

**Example:**
```php
public function index(): ResponseInterface
{
    if (!$this->isUserAuth()) {
        return $this->returnError(401);
    }
    
    $data = $this->request->getAllDatas();
    if (empty($data)) {
        return $this->returnError(400);
    }
    
    // Continue with business logic
}
```

---

#### `isUserAuth(): bool`

```php
protected function isUserAuth(): bool
```

**Description:**  
Checks if the current request is authenticated using Bearer token authentication.

**Parameters:** None

**Return Type:** `bool`

**Return Values:**
- `true` if the request has a valid Bearer token
- `false` if authentication fails or token is missing

**Behavior:**

1. **Extract Authorization Header:**
   - Calls `$this->request->getAuthUser()`
   - Returns `false` if header is missing

2. **Validate Authorization Type:**
   - Checks if authorization type is "Bearer"
   - Returns `false` if type is not "Bearer"

3. **Validate Token:**
   - Creates AuthBearerMiddleware instance
   - Calls `isAuth()` with the token
   - Returns validation result

**Authentication Flow:**
```
isUserAuth() called
    ↓
Get Authorization header
    ├─ NULL → Return false
    └─ EXISTS → Continue
    ↓
Check authorization type
    ├─ NOT "Bearer" → Return false
    └─ "Bearer" → Continue
    ↓
Validate token with middleware
    ├─ VALID → Return true
    └─ INVALID → Return false
```

**Example:**
```php
public function index(): ResponseInterface
{
    if (!$this->isUserAuth()) {
        return $this->returnError(401);
    }
    
    // User is authenticated, proceed with logic
    $data = $this->request->getAllDatas();
    // ...
}
```

**Authorization Header Format:**
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**Token Extraction:**
- Type: `Bearer`
- Token: JWT token string

---

## Usage Patterns

### Creating a Concrete Controller

```php
namespace App\Controllers;

use App\Kernel\AbstractController;
use App\Kernel\Interfaces\ResponseInterface;
use App\Services\Responses\JsonResponse;
use App\Interfaces\AuthenticationInterface;
use App\Services\Connector;

class UserController extends AbstractController
{
    public function __construct(AuthenticationInterface $authMiddleware)
    {
        parent::__construct($authMiddleware);
        $this->connector = new Connector();
    }

    public function index(): ResponseInterface
    {
        // Check authentication
        if (!$this->isUserAuth()) {
            return $this->returnError(401);
        }

        // Get request data
        $data = $this->request->getAllDatas();

        // Business logic
        $response = new JsonResponse();
        $response->setBody(['users' => []]);
        return $response;
    }
}
```

### Handling Different HTTP Methods

```php
public function index(): ResponseInterface
{
    switch ($this->request->getMethod()) {
        case 'GET':
            return $this->handleGet();
        case 'POST':
            return $this->handlePost();
        case 'PUT':
            return $this->handlePut();
        case 'DELETE':
            return $this->handleDelete();
        default:
            return $this->returnError(405);  // Method Not Allowed
    }
}

private function handleGet(): ResponseInterface
{
    // GET logic
}

private function handlePost(): ResponseInterface
{
    // POST logic
}
```

### Accessing Request Data

```php
public function index(): ResponseInterface
{
    // Get all data
    $data = $this->request->getAllDatas();

    // Get specific values
    $id = $data['id'] ?? null;
    $name = $data['name'] ?? null;

    // Get HTTP method
    $method = $this->request->getMethod();

    // Get files
    $files = $this->request->getFile('upload');

    // Get session data
    $userId = $this->request->getSessionValue('user_id');
}
```

### Using the Connector

```php
public function index(): ResponseInterface
{
    $model = new UserModel($this->connector->getConnection());
    $users = $model->getAll();

    $response = new JsonResponse();
    $response->setBody($users);
    return $response;
}
```

### Error Handling

```php
public function index(): ResponseInterface
{
    // Validate request data
    $data = $this->request->getAllDatas();
    
    if (empty($data['id'])) {
        return $this->returnError(400);  // Bad Request
    }

    if (!is_numeric($data['id'])) {
        return $this->returnError(422);  // Unprocessable Entity
    }

    // Check authentication
    if (!$this->isUserAuth()) {
        return $this->returnError(401);  // Unauthorized
    }

    // Continue with business logic
}
```

## Common Controller Patterns

### RESTful CRUD Controller

```php
class ResourceController extends AbstractController
{
    public function __construct(AuthenticationInterface $authMiddleware)
    {
        parent::__construct($authMiddleware);
        $this->connector = new Connector();
    }

    public function index(): ResponseInterface
    {
        switch ($this->request->getMethod()) {
            case 'GET':
                return $this->getResources();
            case 'POST':
                return $this->createResource();
            case 'PUT':
                return $this->updateResource();
            case 'DELETE':
                return $this->deleteResource();
            default:
                return $this->returnError(405);
        }
    }

    private function getResources(): ResponseInterface
    {
        $data = $this->request->getAllDatas();
        $model = new ResourceModel($this->connector->getConnection());
        
        if (isset($data['id'])) {
            $resource = $model->getOne($data['id']);
            if (!$resource) {
                return $this->returnError(404);
            }
        } else {
            $resource = $model->getAll();
        }

        $response = new JsonResponse();
        $response->setBody($resource);
        return $response;
    }

    private function createResource(): ResponseInterface
    {
        if (!$this->isUserAuth()) {
            return $this->returnError(401);
        }

        $data = $this->request->getAllDatas();
        $model = new ResourceModel($this->connector->getConnection());
        $model->add($data);

        $response = new JsonResponse();
        $response->setStatusCode(201);
        $response->setBody(['id' => $model->lastId()]);
        return $response;
    }

    private function updateResource(): ResponseInterface
    {
        if (!$this->isUserAuth()) {
            return $this->returnError(401);
        }

        $data = $this->request->getAllDatas();
        if (!isset($data['id'])) {
            return $this->returnError(400);
        }

        $model = new ResourceModel($this->connector->getConnection());
        $model->update($data['id'], $data);

        $response = new JsonResponse();
        return $response;
    }

    private function deleteResource(): ResponseInterface
    {
        if (!$this->isUserAuth()) {
            return $this->returnError(401);
        }

        $data = $this->request->getAllDatas();
        if (!isset($data['id'])) {
            return $this->returnError(400);
        }

        $model = new ResourceModel($this->connector->getConnection());
        $model->delete($data['id']);

        $response = new JsonResponse();
        return $response;
    }
}
```

### Authenticated Controller

### File Upload Controller

```php
class FileController extends AbstractController
{
    public function __construct(AuthenticationInterface $authMiddleware)
    {
        parent::__construct($authMiddleware);
        $this->connector = new Connector();
    }

    public function index(): ResponseInterface
    {
        if (!$this->isUserAuth()) {
            return $this->returnError(401);
        }

        $files = $this->request->getFile('upload');
        
        if (!$files) {
            return $this->returnError(400);
        }

        foreach ($files as $file) {
            // Process file
        }

        $response = new JsonResponse();
        $response->setStatusCode(201);
        return $response;
    }
}
```

## Best Practices

### 1. Always Call Parent Constructor

```php
// Good
public function __construct(AuthenticationInterface $authMiddleware)
{
    parent::__construct($authMiddleware);
    // Additional initialization
}

// Avoid
public function __construct(AuthenticationInterface $authMiddleware)
{
    // Missing parent::__construct()
}
```

### 2. Check Authentication Early

```php
// Good
public function index(): ResponseInterface
{
    if (!$this->isUserAuth()) {
        return $this->returnError(401);
    }
    // Business logic
}

// Avoid
public function index(): ResponseInterface
{
    // Business logic
    if (!$this->isUserAuth()) {
        return $this->returnError(401);
    }
}
```

### 3. Validate Request Data

```php
// Good
$data = $this->request->getAllDatas();
$id = $data['id'] ?? null;

if ($id === null || !is_numeric($id)) {
    return $this->returnError(400);
}

// Avoid
$id = $data['id'];  // May not exist
```

### 4. Use Appropriate Error Codes

```php
// Good
if (!$this->isUserAuth()) {
    return $this->returnError(401);  // Unauthorized
}

if (!$resource) {
    return $this->returnError(404);  // Not Found
}

// Avoid
return $this->returnError(500);  // Wrong error code
```

### 5. Return ResponseInterface

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

### 6. Separate Concerns

```php
// Good
public function index(): ResponseInterface
{
    switch ($this->request->getMethod()) {
        case 'GET':
            return $this->handleGet();
        case 'POST':
            return $this->handlePost();
    }
}

private function handleGet(): ResponseInterface
{
    // GET logic
}

// Avoid
public function index(): ResponseInterface
{
    // All logic in one method
}
```

## Dependencies

### Internal Dependencies

- **RequestObject** (`App\Kernel\RequestObject`)
  - Provides access to HTTP request data
  - Singleton instance

- **AuthenticationInterface** (`App\Interfaces\AuthenticationInterface`)
  - Interface for authentication middleware
  - Injected via constructor

- **AuthBearerMiddleware** (`App\Middleware\AuthBearerMiddleware`)
  - Validates Bearer tokens
  - Used in `isUserAuth()` method

- **ResponseInterface** (`App\Kernel\Interfaces\ResponseInterface`)
  - Interface for response objects
  - Return type for controller methods

- **ClientErrorResponse** (`App\Services\Responses\ClientErrorResponse`)
  - Used by `returnError()` method

- **ConnectorInterface** (`App\Interfaces\ConnectorInterface`)
  - Database connector interface
  - Initialized by subclasses

## Request Data Access

### Getting All Data

```php
$data = $this->request->getAllDatas();
// Returns: ['id' => 1, 'name' => 'John', ...]
```

### Getting HTTP Method

```php
$method = $this->request->getMethod();
// Returns: 'GET', 'POST', 'PUT', 'DELETE', etc.
```

### Getting Files

```php
$files = $this->request->getFile('upload');
// Returns: [FileUpload, FileUpload, ...] or null
```

### Getting Session Data

```php
$userId = $this->request->getSessionValue('user_id');
// Returns: session value or null
```

### Getting Authorization Header

```php
$auth = $this->request->getAuthUser();
// Returns: ['Bearer', 'token...'] or null
```

## Authentication Flow

### Bearer Token Authentication

```
Request with Authorization header
    ↓
Authorization: Bearer <token>
    ↓
isUserAuth() called
    ↓
Extract header: ['Bearer', '<token>']
    ↓
Check type: 'Bearer' ✓
    ↓
Validate token with middleware
    ├─ VALID → Return true
    └─ INVALID → Return false
    ↓
Controller proceeds or returns 401
```

## Error Responses

### 400 Bad Request

```php
if (empty($data['required_field'])) {
    return $this->returnError(400);
}
```

### 401 Unauthorized

```php
if (!$this->isUserAuth()) {
    return $this->returnError(401);
}
```

### 404 Not Found

```php
if (!$resource) {
    return $this->returnError(404);
}
```

### 405 Method Not Allowed

```php
default:
    return $this->returnError(405);
```

### 422 Unprocessable Entity

```php
if (!is_numeric($id)) {
    return $this->returnError(422);
}
```

## Testing

### Unit Test Example

```php
use PHPUnit\Framework\TestCase;
use App\Controllers\UserController;
use App\Middleware\AuthBearerMiddleware;

class UserControllerTest extends TestCase
{
    private $controller;
    private $authMiddleware;

    protected function setUp(): void
    {
        $this->authMiddleware = new AuthBearerMiddleware();
        $this->controller = new UserController($this->authMiddleware);
    }

    public function testIndexRequiresAuthentication()
    {
        $response = $this->controller->index();
        $this->assertEquals(401, $response->statusCode);
    }

    public function testReturnError()
    {
        $response = $this->controller->returnError(404);
        $this->assertInstanceOf(ClientErrorResponse::class, $response);
    }
}
```

## Related Classes

- **RouterObject** (`App\Kernel\RouterObject`)
  - Instantiates controllers and calls methods

- **RequestObject** (`App\Kernel\RequestObject`)
  - Provides request data

- **AbstractResponse** (`App\Kernel\AbstractResponse`)
  - Base class for responses

- **AuthBearerMiddleware** (`App\Middleware\AuthBearerMiddleware`)
  - Validates Bearer tokens

- **ConnectorInterface** (`App\Interfaces\ConnectorInterface`)
  - Database connection interface

## Related Documentation

- [RouterObject Documentation](./RouterObject.md) - How controllers are instantiated and called
- [RequestObject Documentation](./RequestObject.md) - How to access request data
- [AbstractResponse Documentation](./AbstractResponse.md) - How to create responses

## Changelog

### Version 1.0
- Initial implementation
- Abstract base class for controllers
- Authentication support
- Error handling
- Request data access

## Future Enhancements

- [ ] Authorization checks (beyond authentication)
- [ ] Request validation helpers



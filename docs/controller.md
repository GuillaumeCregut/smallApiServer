# Controller Documentation

1. **AbstractController**: Base class providing common controller functionality
2. **Concrete Controllers**: Specific implementations handling business logic (e.g., HomeController)

The system integrates with the event system (PSR-14) to handle authentication, authorization, database connections, and response generation.

---



## AbstractController Class

### Description

`AbstractController` is the base class for all controllers in the application. It provides common functionality for request handling, response generation, and authentication checks.

### Location
`App\Kernel\AbstractController`

### Properties

```php
protected ConnectorInterface $connector;
protected Request $request;
```

| Property | Type | Description |
|----------|------|-------------|
| `$connector` | `ConnectorInterface` | Database connector for data operations |
| `$request` | `Request` | Current request object with user data |

### Constructor

```php
public function __construct()
{
    $this->request = Request::getRequestInstance();
}
```

**Initialization:**
- Retrieves the singleton `Request` instance
- Makes request data accessible to all controller methods

### Methods

#### `returnError(int $error, ?Exception $e = null): ResponseInterface`

Returns a client error response (4xx status codes).

**Parameters:**
- `$error` (int): HTTP status code (400, 401, 403, 404, 422, etc.)
- `$e` (Exception, optional): Exception object for debugging

**Returns:** `ClientErrorResponse` object

**Usage:**

```php
// Return 401 unauthorized
if (!$this->isUserAuth()) {
    return $this->returnError(401);
}

// Return 404 not found
if ($resource === null) {
    return $this->returnError(404);
}

// Return 422 unprocessable entity
if (!$this->validateData($data)) {
    return $this->returnError(422, new Exception('Validation failed'));
}
```

#### `isUserAuth(): bool`

Checks if the current request has an authenticated user.

**Returns:** `true` if user is authenticated, `false` otherwise

**Implementation:** Checks `$this->request->isConnected()`

**Usage:**

```php
public function deleteResource(): ResponseInterface
{
    // Require authentication
    if (!$this->isUserAuth()) {
        return $this->returnError(401);
    }
    
    // Proceed with deletion
    return $this->returnJson(['deleted' => true]);
}
```

#### `returnJson(mixed $body = null, ?int $code = 200): ResponseInterface`

Returns a JSON response with specified status code.

**Parameters:**
- `$body` (mixed): Data to encode as JSON (arrays, objects, strings)
- `$code` (int): HTTP status code (default: 200)

**Returns:** `JsonResponse` object

**HTTP Status Codes:**
- `200`: OK - Successful GET, PUT
- `201`: Created - Successful POST
- `204`: No Content - Successful DELETE
- `400`: Bad Request
- `401`: Unauthorized
- `422`: Unprocessable Entity
- `500`: Server Error

**Usage:**

```php
// Success response with data
return $this->returnJson(['id' => 1, 'name' => 'John'], 200);

// Created response
return $this->returnJson(['id' => 5, 'created' => true], 201);

// No content response
return $this->returnJson(null, 204);

// Just send 204 without body
return $this->returnJson(code: 204);
```

### Common Controller Patterns

#### Pattern 1: Resource Retrieval (GET)

```php
public function getResource(): ResponseInterface
{
    $id = $this->request->getData('id');
    
    if (!$id) {
        return $this->returnError(400);
    }
    
    $resource = $this->getResourceFromDB($id);
    
    if (!$resource) {
        return $this->returnError(404);
    }
    
    return $this->returnJson($resource, 200);
}
```

#### Pattern 2: Resource Creation (POST)

```php
public function createResource(): ResponseInterface
{
    $data = $this->request->getAllDatas();
    
    if (!$this->validateInput($data)) {
        return $this->returnError(422);
    }
    
    $created = $this->saveResourceToDB($data);
    
    return $this->returnJson($created, 201);
}
```

#### Pattern 3: Resource Update (PUT/PATCH)

```php
public function updateResource(): ResponseInterface
{
    $id = $this->request->getData('id');
    $data = $this->request->getAllDatas();
    
    if (!$this->validateInput($data)) {
        return $this->returnError(422);
    }
    
    $updated = $this->updateResourceInDB($id, $data);
    
    return $this->returnJson($updated, 200);
}
```

#### Pattern 4: Resource Deletion (DELETE)

```php
public function deleteResource(): ResponseInterface
{
    if (!$this->isUserAuth()) {
        return $this->returnError(401);
    }
    
    $id = $this->request->getData('id');
    
    $this->deleteResourceFromDB($id);
    
    return $this->returnJson(null, 204);
}
```

#### Pattern 5: Protected Routes

```php
public function sensitiveOperation(): ResponseInterface
{
    if (!$this->isUserAuth()) {
        return $this->returnError(401);
    }
    
    $user = $this->request->getUser();
    
    if (!$user->hasRole('ADMIN')) {
        return $this->returnError(403);
    }
    
    // Proceed with sensitive operation
    return $this->returnJson(['success' => true]);
}
```


---

## Complete Request Lifecycle Example

### Example 1: GET Request (No Authentication)

```
1. Client Request:
   GET / HTTP/1.1

2. Kernel receives request
   ↓ Routes to HomeController::getDatas()

3. HomeController::getDatas() executes:
   - Checks if 'id' parameter exists
   - Queries database for data
   - Returns JSON response

4. Response:
   HTTP/1.1 200 OK
   Content-Type: application/json
   
   [{"id": 1, "name": "Item 1"}, ...]
```

### Example 2: DELETE Request (Requires Authentication)

```
1. Client Request:
   DELETE / HTTP/1.1
   Authorization: Bearer <token>
   
   {"id": 5}

2. Kernel receives request
   ↓ Dispatches CallAuthKernelEvent
   ↓ AuthManagerMiddleware validates token
   ↓ Sets user in Request
   ↓ Routes to HomeController::deleteData()

3. HomeController::deleteData() executes:
   - Checks isUserAuth()
   - If not authenticated → return 401
   - If authenticated:
     - Delete record from database
     - Return 204 (no content)

4. Response:
   HTTP/1.1 204 No Content
```

### Example 3: POST Request with Validation

```
1. Client Request:
   POST / HTTP/1.1
   Content-Type: application/json
   
   {"name": "New Item"}

2. Kernel routes to HomeController::addData()

3. HomeController::addData() executes:
   - Extracts data: {"name": "New Item"}
   - Validates data
   - If invalid → return 422
   - If valid:
     - Save to database
     - Return 201 with new record

4. Response:
   HTTP/1.1 201 Created
   Content-Type: application/json
   
   {"id": 10, "name": "New Item"}
```

---

## Creating New Controllers

### Step 1: Create Controller Class

```php
<?php

namespace App\Controllers;

use App\Kernel\AbstractController;
use App\Kernel\Interfaces\ResponseInterface;
use App\Kernel\Responses\ClientErrorResponse;

class UserController extends AbstractController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): ResponseInterface
    {
        // GET /user
        return $this->returnJson(['users' => []], 200);
    }

    public function show(): ResponseInterface
    {
        // GET /user with id parameter
        $id = $this->request->getData('id');
        return $this->returnJson(['id' => $id], 200);
    }

    public function create(): ResponseInterface
    {
        // POST /user
        return $this->returnJson(['created' => true], 201);
    }

    public function update(): ResponseInterface
    {
        // PUT /user
        return $this->returnJson(['updated' => true], 200);
    }

    public function delete(): ResponseInterface
    {
        // DELETE /user
        if (!$this->isUserAuth()) {
            return $this->returnError(401);
        }
        return $this->returnJson(null, 204);
    }
}
```

### Step 2: Add Routes to Router

```php
class Router
{
    public static function getRoutes(): array
    {
        return [
            'user' => [
                'GET' => [UserController::class, 'index'],
                'POST' => [UserController::class, 'create'],
                'PUT' => [UserController::class, 'update'],
                'DELETE' => [UserController::class, 'delete'],
            ],
        ];
    }
}
```

### Step 3: Use in Client

```bash
# List all users
curl GET http://example.com/user

# Get specific user
curl GET "http://example.com/user?id=5"

# Create user
curl -X POST http://example.com/user \
  -H "Content-Type: application/json" \
  -d '{"name": "John"}'

# Update user
curl -X PUT http://example.com/user \
  -H "Content-Type: application/json" \
  -d '{"id": 5, "name": "Jane"}'

# Delete user
curl -X DELETE http://example.com/user \
  -H "Authorization: Bearer <token>" \
  -d '{"id": 5}'
```

---

## Best Practices

### 1. Always Extend AbstractController

```php
✅ Good:
class ProductController extends AbstractController { }

❌ Bad:
class ProductController { }  // Missing base functionality
```

### 2. Check Authentication for Sensitive Operations

```php
✅ Good:
public function deleteProduct(): ResponseInterface
{
    if (!$this->isUserAuth()) {
        return $this->returnError(401);
    }
    // Delete logic...
}

❌ Bad:
public function deleteProduct(): ResponseInterface
{
    // Delete without checking auth!
}
```

### 3. Validate Input Data

```php
✅ Good:
public function createUser(): ResponseInterface
{
    $data = $this->request->getAllDatas();
    if (!$this->validateUserData($data)) {
        return $this->returnError(422);
    }
    // Create user...
}

❌ Bad:
public function createUser(): ResponseInterface
{
    $data = $this->request->getAllDatas();
    // Save directly without validation!
}
```

### 4. Use Appropriate HTTP Status Codes

```php
✅ Good:
- 200: Successful GET, PUT
- 201: Successful POST (resource created)
- 204: Successful DELETE (no content)
- 400: Bad request (invalid data)
- 401: Unauthorized (auth required)
- 403: Forbidden (auth insufficient)
- 404: Not found
- 422: Unprocessable entity (validation failed)

❌ Bad:
- Always returning 200 for everything
- Not distinguishing between error types
```

### 5. Return Consistent Response Format

```php
✅ Good:
// Successful
return $this->returnJson(['data' => $users], 200);

// Error
return $this->returnError(404);

❌ Bad:
// Inconsistent formats
return "User not found";
return ['error' => 'User not found'];
```

### 6. Use Meaningful Route Names

```php
✅ Good routes:
- /users
- /products
- /orders/by-date
- /api/v1/users

❌ Bad routes:
- /get-users
- /create-product
- /deletedata
- /script.php?action=delete
```

---

## Error Handling

### HTTP Status Code Reference

| Code | Meaning | When to Use |
|------|---------|------------|
| 200 | OK | Successful GET, PUT, PATCH |
| 201 | Created | Successful POST |
| 204 | No Content | Successful DELETE, no response body |
| 400 | Bad Request | Invalid request format or data |
| 401 | Unauthorized | Authentication required but missing |
| 403 | Forbidden | Authenticated but insufficient permissions |
| 404 | Not Found | Resource doesn't exist |
| 405 | Method Not Allowed | HTTP method not supported for route |
| 422 | Unprocessable Entity | Validation failed on input data |
| 500 | Server Error | Application error (handled by Kernel) |

### Error Response Examples

```php
// 400 - Bad Request
return $this->returnError(400);  // Malformed request

// 401 - Unauthorized
return $this->returnError(401);  // Login required

// 403 - Forbidden
return $this->returnError(403);  // Insufficient permissions

// 404 - Not Found
return $this->returnError(404);  // Resource doesn't exist

// 422 - Unprocessable Entity
return $this->returnError(422);  // Validation failed
```

---

## Related Documentation

- [Authentication System](autentication.md)
- [Response System](response.md)
- [Request](request.md)
- [Kernel](kernel.md)
- [Router](router.md)
- [Controller example (HomeController)](homecontroller.md)

---

## Summary

The controller system provides:

| Component | Purpose |
|-----------|---------|
| **AbstractController** | Base class with common controller methods |
| **Concrete Controllers** | Specific business logic implementation |

Controllers follow REST principles:
- **GET**: Retrieve resources
- **POST**: Create resources
- **PUT**: Update resources
- **DELETE**: Remove resources

All controllers must extend `AbstractController` and return `ResponseInterface` objects, ensuring consistent response handling and integration with the framework's event system.

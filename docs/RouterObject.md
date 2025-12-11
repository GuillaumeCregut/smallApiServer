# RouterObject Documentation

## Overview

`RouterObject` is the core routing component of the SmallMVC framework. It handles HTTP request routing by mapping URI paths to their corresponding controllers and methods. This class acts as the central dispatcher that processes incoming requests and directs them to the appropriate controller action.

**Location:** `Kernel/RouterObject.php`  
**Namespace:** `App\Kernel`

## Class Definition

```php
class RouterObject
```

## Properties

### Private Properties

#### `$routeCall` (string)
- **Type:** `string`
- **Description:** Stores the URI path extracted from the current HTTP request
- **Scope:** Private
- **Initialized in:** Constructor via `RequestObject::getURI()`

#### `$request` (RequestObject)
[RequestObject Documentation](./RequestObject.md)
- **Type:** `RequestObject`
- **Description:** Singleton instance of the RequestObject containing all HTTP request data
- **Scope:** Private
- **Initialized in:** Constructor via `RequestObject::getRequestInstance()`

#### `$routes` (array)
- **Type:** `array`
- **Description:** Associative array mapping URI paths to controller class names and method names
- **Scope:** Private
- **Default Routes:**
  ```php
  [
      '' => ['\App\Controllers\HomeController', 'index'],
      'items' => ['\App\Controllers\ItemController', 'index'],
      'categories' => ['\App\Controllers\CategoryController', 'index'],
  ]
  ```

## Methods

### Constructor

```php
public function __construct()
```

**Description:**  
Initializes the RouterObject by obtaining the current request instance and extracting the URI path.

**Parameters:** None

**Return Type:** void

**Behavior:**
- Retrieves the singleton RequestObject instance
- Extracts the URI path from the request using `getURI()`
- Stores the URI in `$routeCall` for later routing

**Example:**
```php
$router = new RouterObject();
```

---

### route()

```php
public function route(): ResponseInterface
```

**Description:**  
Main routing method that processes the HTTP request and dispatches it to the appropriate controller method. Returns a response object that can be sent to the client.

**Parameters:** None

**Return Type:** `ResponseInterface`

**Return Values:**
- **Success:** ResponseInterface object from the executed controller method
- **Route Not Found:** `ClientErrorResponse(404)` - When the URI doesn't match any registered route
- **Exception During Execution:** `ErrorResponse(500)` - When an exception occurs during controller execution

**Behavior:**

1. **Route Validation:**
   - Checks if the extracted URI (`$routeCall`) exists as a key in the `$routes` array
   - If not found, returns a 404 ClientErrorResponse

2. **Route Matching:**
   - Retrieves the matching route configuration (controller class and method name)

3. **Controller Instantiation:**
   - Creates an instance of the AuthBearerMiddleware
   - Instantiates the controller class with the middleware as a dependency
   - Calls the specified method on the controller

4. **Exception Handling:**
   - Catches any exceptions thrown during controller execution
   - Returns a 500 ErrorResponse if an exception occurs

**Example:**
```php
$router = new RouterObject();
$response = $router->route();
$response->send();
```

**Flow Diagram:**
```
route() called
    ↓
Check if URI exists in $routes
    ├─ NO → Return ClientErrorResponse(404)
    └─ YES → Continue
    ↓
Extract controller class and method
    ↓
Instantiate AuthBearerMiddleware
    ↓
Instantiate Controller with middleware
    ↓
Call controller method
    ├─ SUCCESS → Return ResponseInterface
    └─ EXCEPTION → Return ErrorResponse(500)
```

## Route Configuration

### Adding New Routes

To add a new route, add an entry to the `$routes` array in the constructor or as a class property:

```php
private array $routes = [
    '' => ['\App\Controllers\HomeController', 'index'],
    'items' => ['\App\Controllers\ItemController', 'index'],
    'categories' => ['\App\Controllers\CategoryController', 'index'],
    'users' => ['\App\Controllers\UserController', 'index'],  // New route
    'products' => ['\App\Controllers\ProductController', 'index'],  // New route
];
```

**Route Format:**
- **Key:** URI path (without leading slash)
- **Value:** Array containing:
  - `[0]` - Fully qualified controller class name (with namespace)
  - `[1]` - Method name to call on the controller

### Route Examples

| URI | Controller | Method |
|-----|-----------|--------|
| `/` | `HomeController` | `index` |
| `/items` | `ItemController` | `index` |
| `/categories` | `CategoryController` | `index` |

## Dependencies

### Internal Dependencies

- **RequestObject** (`App\Kernel\RequestObject`)
  - Used to get the singleton request instance
  - Provides URI extraction via `getURI()`

- **AuthBearerMiddleware** (`App\Middleware\AuthBearerMiddleware`)
  - Instantiated for each request
  - Injected into controllers for authentication handling

- **ResponseInterface** (`App\Kernel\Interfaces\ResponseInterface`)
  - Interface for all response objects
  - Implemented by response classes

### Response Classes Used

- **ClientErrorResponse** (`App\Services\Responses\ClientErrorResponse`)
  - Used for 404 Not Found errors

- **ErrorResponse** (`App\Services\Responses\ErrorResponse`)
  - Used for 500 Internal Server Error

## Usage Examples

### Basic Usage

```php
<?php
// In public/index.php
require_once '../vendor/Autoload.php';

use App\Kernel\RouterObject;

$router = new RouterObject();
$response = $router->route();
$response->send();
```

### Handling Different Routes

```php
// Request to /items
// RouterObject will:
// 1. Extract 'items' from the URI
// 2. Find the matching route in $routes
// 3. Instantiate ItemController with AuthBearerMiddleware
// 4. Call the index() method
// 5. Return the response

// Request to /unknown
// RouterObject will:
// 1. Extract 'unknown' from the URI
// 2. Not find it in $routes
// 3. Return ClientErrorResponse(404)
```

## Error Handling

### 404 Not Found

**When:** The requested URI doesn't match any route in the `$routes` array

**Response:**
```php
ClientErrorResponse(404)
```

**HTTP Status:** 404 Not Found

### 500 Internal Server Error

**When:** An exception is thrown during controller instantiation or method execution

**Response:**
```php
ErrorResponse(500)
```

**HTTP Status:** 500 Internal Server Error

**Common Causes:**
- Controller class doesn't exist
- Method doesn't exist on controller
- Exception thrown in controller logic
- Database connection failure
- File not found errors

## Best Practices

### 1. Route Organization

Keep routes organized and logical:
```php
private array $routes = [
    '' => ['\App\Controllers\HomeController', 'index'],
    'api/users' => ['\App\Controllers\UserController', 'index'],
    'api/products' => ['\App\Controllers\ProductController', 'index'],
    'api/orders' => ['\App\Controllers\OrderController', 'index'],
];
```

### 2. Controller Naming Convention

Use consistent naming for controllers:
- Suffix with `Controller`
- Use PascalCase
- Example: `UserController`, `ProductController`, `OrderController`

### 3. Method Naming

Use consistent method names:
- `index()` - Main entry point for the route
- Consider using additional methods for different HTTP methods

### 4. Exception Handling

Ensure controllers handle exceptions properly:
```php
public function index(): ResponseInterface
{
    try {
        // Business logic
    } catch (\Exception $e) {
        return new ErrorResponse(500);
    }
}
```

### 5. Middleware Integration

The router automatically injects `AuthBearerMiddleware`. Ensure your controllers:
- Accept the middleware in the constructor
- Use it for authentication checks when needed

```php
public function __construct(AuthenticationInterface $authMiddleware)
{
    parent::__construct($authMiddleware);
}
```

## Performance Considerations

1. **Route Lookup:** O(1) - Uses array key lookup
2. **Controller Instantiation:** Happens on every request
3. **Middleware Creation:** New instance created for each request

## Testing

### Unit Test Example

```php
use PHPUnit\Framework\TestCase;
use App\Kernel\RouterObject;

class RouterObjectTest extends TestCase
{
    public function testValidRoute()
    {
        $router = new RouterObject();
        $response = $router->route();
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function test404Route()
    {
        // Mock request to return non-existent route
        $response = $router->route();
        $this->assertInstanceOf(ClientErrorResponse::class, $response);
    }
}
```

## Related Classes

- **RequestObject** - Handles HTTP request data
- **AbstractController** - Base class for all controllers
- **ResponseInterface** - Interface for response objects
- **AuthBearerMiddleware** - Authentication middleware
- **ClientErrorResponse** - 4xx error responses
- **ErrorResponse** - 5xx error responses

## Changelog

### Version 1.0
- Initial implementation
- Basic route matching
- Exception handling
- Middleware integration

## Future Enhancements

- [ ] Support for regex route patterns
- [ ] Route grouping and prefixes
- [ ] Named routes
- [ ] Route caching
- [ ] Multiple middleware support

---

## Related Documentation

- [RequestObject Documentation](./RequestObject.md) - Learn about HTTP request handling and data extraction
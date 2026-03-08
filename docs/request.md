# Request Class Documentation

## Overview

The `Request` class is a singleton that encapsulates all HTTP request data and provides a unified interface to access GET parameters, POST data, files, headers, cookies, sessions, and user information. It implements the Singleton pattern to ensure a single instance throughout the application lifecycle.

**Key Features:**
- **Singleton Pattern**: Single instance throughout application lifecycle
- **Data Consolidation**: Merges GET, POST, and custom data sources
- **File Handling**: Automatic file upload processing
- **Route Integration**: Works with `RouteCompiler` for intelligent URL pattern matching
- **Parameter Extraction**: Automatically extracts parameters from URLs using route patterns
- **URI Sanitization**: Protects against injection attacks

---

## Singleton Pattern

The `Request` class uses the Singleton pattern to maintain a single instance across the entire application.

### Initialization

```php
use App\Kernel\Request;

// Initialize on first use
$request = Request::initInstance(
    $_SERVER,      // Server data
    [],            // Body data
    $_GET,         // GET parameters
    $_POST,        // POST data
    $_FILES,       // Uploaded files
    $_SESSION,     // Session data
    getallheaders(), // HTTP headers
    $_COOKIE       // Cookies
);
```

### Getting the Instance

```php
// Get the already initialized instance
$request = Request::getRequestInstance();

// If not yet initialized, initInstance will be called automatically with superglobals
$request = Request::getRequestInstance();
```

### Resetting the Instance

For testing purposes, you can reset the singleton instance:

```php
Request::resetInstance();
```

---

## Request Data

### Accessing Request Data

The Request class automatically merges GET, POST, and custom data into a single array accessible via several methods.

#### Getting All Data

```php
$allData = $request->getAllDatas();
// Returns: ['name' => 'John', 'email' => 'john@example.com', 'id' => 1]
```

#### Getting Specific Data

```php
$name = $request->getData('name');           // 'John'
$email = $request->getData('email');         // 'john@example.com'
$missing = $request->getData('nonexistent');  // null
```

#### Setting Data

```php
$request->addParam('custom_field', 'value');
$value = $request->getParam('custom_field');  // 'value'
```

### Data Merging Order

The Request class merges data from multiple sources in this order:
1. GET parameters (lowest priority)
2. POST data
3. Custom body data
4. Explicit `setData()` calls (highest priority)

```php
Request::initInstance([], ['id' => 10], ['id' => 5], ['id' => 1], [], [], []);
// Result: getData('id') = 10 (POST takes precedence)
```

---

## HTTP Methods

### Getting Request Method

```php
$method = $request->getMethod();

if ($method === 'GET') {
    // Handle GET request
}

if ($method === 'POST') {
    // Handle POST request
}

if ($method === 'PUT') {
    // Handle PUT request
}

if ($method === 'DELETE') {
    // Handle DELETE request
}
```

---

## URI and Routing

The Request class integrates with the `RouteCompiler` to perform intelligent URL pattern matching and parameter extraction.

### Route Patterns with Placeholders

Routes are defined with placeholders using curly braces `{paramName}`. The `RouteCompiler` converts these routes into regex patterns for flexible matching.

#### Route Pattern Syntax

```php
// Simple static route
'user'

// Route with single parameter
'user/{id}'

// Route with multiple parameters
'user/{id}/post/{postId}'

// Route with string slugs
'product/{slug}'
```

### Getting the URI with Route Matching

The `getURI()` method now accepts a routes array to match against:

```php
$routes = [
    'user' => [...],
    'user/{id}' => [...],
    'user/{id}/post/{postId}' => [...],
];

// URL: /user/42
$uri = $request->getURI($routes);
// Result: 'user/{id}'  (matched pattern, not literal path)

// Extracted parameters automatically stored:
$id = $request->getData('id');  // 42
```

### RouteCompiler

The `RouteCompiler` class handles route pattern matching using regex:

```php
use App\Kernel\RouteCompiler;

// Convert route pattern to regex
$pattern = RouteCompiler::compile('user/{id}');
// Result: '~^user/(?P<id>[^/]+)$~'

// Find matching route
$result = RouteCompiler::findRoute('user/42', $routes);
// Result: [
//     'routeName' => 'user/{id}',
//     'id' => '42'
// ]
```

#### Compilation Examples

| Route Pattern | Compiled Regex | Matches | Doesn't Match |
|--|--|--|--|
| `user` | `~^user$~` | `/user` | `/user/`, `/user/1` |
| `user/{id}` | `~^user/(?P<id>[^/]+)$~` | `/user/42`, `/user/john` | `/user/42/`, `/user/` |
| `user/{id}/post/{postId}` | `~^user/(?P<id>[^/]+)/post/(?P<postId>[^/]+)$~` | `/user/42/post/7` | `/user/42/post`, `/user/42` |
| `product/{slug}` | `~^product/(?P<slug>[^/]+)$~` | `/product/my-item` | `/product/my/item` |

**Key Rules:**
- Placeholders `{name}` match `[^/]+` (anything except forward slash)
- Routes must match exactly (no partial matches)
- Trailing slashes must not be present in URL
- Parameter values are always strings from regex

### Parameter Extraction

Parameters in the URL are automatically extracted and stored in the Request's data:

```php
// URL: /user/42
$routes = ['user/{id}' => [...]];
$request->getURI($routes);

// Automatically extracted:
$id = $request->getData('id');     // '42' (string)
$intId = (int) $request->getData('id');  // 42 (if you need int)
```

#### Multiple Parameter Extraction

```php
// URL: /user/123/post/456
$routes = ['user/{id}/post/{postId}' => [...]];
$request->getURI($routes);

// Both parameters extracted:
$userId = $request->getData('id');          // '123'
$postId = $request->getData('postId');      // '456'
```

### Route Matching Logic

The `RouteCompiler::findRoute()` method:
1. Iterates through provided routes in order
2. For each route, compiles pattern and attempts regex match
3. Returns first matching route with extracted parameters
4. Returns `null` if no routes match

```php
$routes = [
    'user' => [...],              // Checked 1st
    'user/{id}' => [...],         // Checked 2nd
    'user/{id}/post/{postId}' => [...],  // Checked 3rd
];

// URL: /user/42/post/7
// Matches: 'user/{id}/post/{postId}' (most specific)
// Returns: ['routeName' => '...', 'id' => '42', 'postId' => '7']
```

### Route Matching Examples

#### Static Route

```php
$routes = [
    'user' => ['GET' => [...], 'POST' => [...]],
];

$request->getURI($routes);
// URL: /user
// Result: 'user'
// No parameters extracted
```

#### Single Parameter

```php
$routes = [
    'user/{id}' => ['GET' => [...], 'PUT' => [...]],
];

$request->getURI($routes);
// URL: /user/42
// Result: 'user/{id}'
// getData('id') = '42'
```

#### Multiple Parameters

```php
$routes = [
    'user/{userId}/post/{postId}' => ['GET' => [...], 'DELETE' => [...]],
];

$request->getURI($routes);
// URL: /user/123/post/456
// Result: 'user/{userId}/post/{postId}'
// getData('userId') = '123'
// getData('postId') = '456'
```

#### String Slugs

```php
$routes = [
    'product/{slug}' => ['GET' => [...]],
];

$request->getURI($routes);
// URL: /product/my-awesome-product
// Result: 'product/{slug}'
// getData('slug') = 'my-awesome-product'
```

### Parameter Type Conversion

Route parameters are always extracted as strings. Convert to appropriate types as needed:

```php
$id = $request->getData('id');  // '42' (string from regex)

// Convert to integer
$intId = (int) $id;             // 42

// Convert to string (already is)
$strId = (string) $id;          // '42'

// Check if numeric
if (is_numeric($id)) {
    $intId = (int) $id;
}
```

### Route Matching Specificity

More specific routes should be defined before less specific ones:

```php
// Good order (specific to general)
$routes = [
    'user/{id}/post/{postId}' => [...],  // Most specific
    'user/{id}' => [...],
    'user' => [...],                     // Least specific
];

// If reversed, less specific routes would match first
```

### URI Sanitization

URIs are sanitized using `FILTER_SANITIZE_URL` before route matching to prevent injection attacks:

```php
$server = ['REQUEST_URI' => '/user/<script>alert("xss")</script>'];
$request = Request::initInstance($server, [], [], [], [], [], []);
// XSS attempt is sanitized and won't match routes
```

### Complete Routing Example

```php
// Define routes
$routes = [
    '' => ['GET' => [HomeController::class, 'index']],
    'user' => [
        'GET' => [UserController::class, 'listAll'],
        'POST' => [UserController::class, 'create'],
    ],
    'user/{id}' => [
        'GET' => [UserController::class, 'getOne'],
        'PUT' => [UserController::class, 'update'],
        'DELETE' => [UserController::class, 'delete'],
    ],
    'user/{userId}/post/{postId}' => [
        'GET' => [UserController::class, 'getPost'],
    ],
];

// In Kernel or Router
$uri = $request->getURI($routes);

if ($uri === null) {
    // Route not found - return 404
    return $this->returnError(404);
}

// Get matched route configuration
$routeConfig = $routes[$uri][$request->getMethod()] ?? null;
if (!$routeConfig) {
    // HTTP method not allowed
    return $this->returnError(405);
}

// Extract controller and method
[$controller, $method] = $routeConfig;

// Parameters automatically available
$userId = $request->getData('userId');   // If matched from {userId}
$postId = $request->getData('postId');   // If matched from {postId}

// Dispatch to controller
$instance = new $controller();
return $instance->$method();  // $this->getURI($routes) already called
```

### Fallback: No Routes Provided

If no routes are provided to `getURI()`, it returns the raw sanitized path:

```php
$request->getURI();  // No routes array
// URL: /user/42
// Result: 'user/42' (literal path, not best practice)

// Better approach: always provide routes
$uri = $request->getURI($routes);
```

---

## Files Handling

### Getting All Files

```php
$files = $request->getFiles();
// Returns: ['documents' => [FileUpload, FileUpload], ...]
```

### Getting Specific File

```php
$fileArray = $request->getFile('documents');
// Returns: [FileUpload, FileUpload, ...] or null if not found

if ($fileArray) {
    foreach ($fileArray as $file) {
        // $file is a FileUpload instance
        $file->move('/uploads/');
    }
}
```

### FileUpload Instance

Each file is converted to a `FileUpload` instance:

```php
$file = $request->getFile('profile_photo')[0];

// Example methods available on FileUpload
// $file->getName();
// $file->getType();
// $file->getSize();
// $file->getTemporaryPath();
// $file->move('/uploads/');
```

### File Upload Example

```php
public function uploadProfilePhoto(): ResponseInterface
{
    $files = $request->getFile('photo');
    
    if (!$files) {
        return $this->returnError(400);  // No file uploaded
    }

    $file = $files[0];
    $uploadPath = '/uploads/profiles/';
    
    try {
        $file->move($uploadPath);
        return $this->returnJson(['message' => 'File uploaded'], 201);
    } catch (Exception $e) {
        return $this->returnError(500);
    }
}
```

---

## Headers and Cookies

### Getting Headers

```php
$contentType = $request->getHeaders('Content-Type');
$authorization = $request->getHeaders('Authorization');

// Returns null if header not found
$custom = $request->getHeaders('X-Custom-Header') ?? 'default';
```

### Getting Cookies

```php
$allCookies = $request->getCookies();
// Returns: ['session_id' => 'abc123', 'theme' => 'dark']

$sessionId = $request->getCookie('session_id');  // 'abc123'
$missing = $request->getCookie('nonexistent');    // null
```

### Header/Cookie Usage Example

```php
public function handleRequest(): ResponseInterface
{
    // Check authorization header
    $token = $request->getHeaders('Authorization');
    if (!$token) {
        return $this->returnError(401);  // Unauthorized
    }

    // Check session cookie
    $sessionId = $request->getCookie('PHPSESSID');
    if (!$sessionId) {
        return $this->returnError(403);  // Forbidden
    }

    return $this->returnJson(['authenticated' => true]);
}
```

---

## Sessions

### Getting Session Values

```php
$userId = $request->getSessionValue('user_id');
$theme = $request->getSessionValue('theme') ?? 'light';
```

### Setting Session Values

```php
$request->setSessionValue('user_id', 123);
$request->setSessionValue('theme', 'dark');

// Values are also stored in $_SESSION automatically
echo $_SESSION['user_id'];  // 123
```

### Session Example

```php
public function login(): ResponseInterface
{
    $username = $request->getData('username');
    $password = $request->getData('password');

    $user = $this->repo->findByUserNameCredentials($username, $password);
    
    if (!$user) {
        return $this->returnError(401);
    }

    // Store user in session
    $request->setSessionValue('user_id', $user->getId());
    $request->setUser($user);

    return $this->returnJson(['message' => 'Logged in'], 200);
}
```

---

## User Management

### Getting the Connected User

```php
$user = $request->getUser();
// Returns User object or null if not authenticated
```

### Setting the User

```php
$user = new User();
$user->setId(123);
$user->setUsername('john');

$request->setUser($user);
```

### Checking Authentication

```php
if ($request->isConnected()) {
    // User is authenticated
    $user = $request->getUser();
    echo "Hello " . $user->getUsername();
} else {
    // User is not authenticated
    return $this->returnError(401);
}
```

### Complete Authentication Flow

```php
public function getCurrentUser(): ResponseInterface
{
    if (!$request->isConnected()) {
        return $this->returnError(401);
    }

    $user = $request->getUser();
    
    return $this->returnJson([
        'id' => $user->getId(),
        'username' => $user->getUsername(),
        'roles' => $user->getRoles()
    ]);
}
```

---

## Custom Parameters

### Adding Custom Parameters

```php
$request->addParam('resource_id', 123);
$request->addParam('action', 'view');
```

### Retrieving Custom Parameters

```php
$resourceId = $request->getParam('resource_id');     // 123
$action = $request->getParam('action');              // 'view'
$missing = $request->getParam('nonexistent');        // null
```

### Getting All Custom Parameters

```php
$allParams = $request->getParams();
// Returns: ['resource_id' => 123, 'action' => 'view']
```

### Middleware Pattern Example

```php
class AuthMiddleware
{
    public function handle(Request $request): bool
    {
        $token = $request->getHeaders('Authorization');
        if (!$token) {
            return false;
        }

        $user = $this->validateToken($token);
        if (!$user) {
            return false;
        }

        $request->setUser($user);
        $request->addParam('authenticated', true);
        
        return true;
    }
}
```

---

## Referrer Validation

### Checking Referrer

```php
if ($request->isRefererValid()) {
    // Request comes from same host and port
    // Safe to proceed
} else {
    // Request might be from CSRF attack
    return $this->returnError(403);
}
```

### How Referrer Validation Works

The Request class validates the HTTP Referer header:

1. Extracts host and port from Referer header
2. Extracts host and port from Host header
3. Compares they match

```php
// Valid: Both from localhost:8000
$server = [
    'HTTP_REFERER' => 'http://localhost:8000/page',
    'HTTP_HOST' => 'localhost:8000'
];
// isRefererValid() = true

// Invalid: Different domain
$server = [
    'HTTP_REFERER' => 'http://evil.com/attack',
    'HTTP_HOST' => 'localhost:8000'
];
// isRefererValid() = false

// Invalid: No referer
$server = [
    'HTTP_HOST' => 'localhost:8000'
    // No HTTP_REFERER
];
// isRefererValid() = false
```

### CSRF Protection Pattern

```php
public function deleteUser(): ResponseInterface
{
    // Verify request comes from same origin
    if (!$request->isRefererValid()) {
        return $this->returnError(403);  // Forbidden
    }

    $id = $request->getData('id');
    $this->repo->delete($id);

    return $this->returnJson(null, 204);
}
```

---

## Server Information

### Getting Server Values

```php
$protocol = $request->getServer('SERVER_PROTOCOL');  // 'HTTP/1.1'
$method = $request->getMethod();      // 'GET'
$host = $request->getServer('HTTP_HOST');             // 'localhost:8000'
$uri = $request->getServer('REQUEST_URI');            // '/user/123?id=1'
```

### Available Server Variables

Common server variables you can access:

```php
$request->getServer('REQUEST_METHOD');    // GET, POST, PUT, DELETE, etc.
$request->getServer('REQUEST_URI');       // Full URI with query string
$request->getServer('HTTP_HOST');         // Host and port
$request->getServer('SERVER_PROTOCOL');   // HTTP/1.1
$request->getServer('SCRIPT_NAME');       // Script filename
$request->getServer('PATH_INFO');         // Additional path info
$request->getServer('QUERY_STRING');      // Query string after ?
```

---

## Usage in Controllers

### Complete Example: UserController

```php
namespace App\Controllers;

use App\Kernel\AbstractController;
use App\Kernel\Connector\Hydrator;
use App\Kernel\Interfaces\ResponseInterface;
use App\Security\User;
use App\Security\UserRepository;

class UserController extends AbstractController
{
    private UserRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new UserRepository();
    }

    // Retrieve all users or one user by ID
    public function get(): ResponseInterface
    {
        $id = $this->request->getData('id') ?? null;
        
        if ($id) {
            // Single user
            $user = $this->repo->find($id);
            if (!$user) {
                return $this->returnError(404);
            }
            return $this->returnJson([
                'id' => $user->getId(),
                'name' => $user->getName(),
                'username' => $user->getUsername()
            ]);
        }

        // All users
        $users = $this->repo->findAll();
        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'username' => $user->getUsername()
            ];
        }
        return $this->returnJson($result);
    }

    // Create a new user
    public function add(): ResponseInterface
    {
        $userDatas = $this->request->getAllDatas();
        
        // Validate data
        if (empty($userDatas['name']) || empty($userDatas['username'])) {
            return $this->returnError(422);  // Unprocessable Entity
        }

        // Create entity from request data
        $user = Hydrator::hydrate(new User(), $userDatas);
        $user->addRole('USER');
        $user->setNewPassword($user->getPassword());

        // Save to database
        $savedUser = $this->repo->save($user);
        if (!$savedUser) {
            return $this->returnError(500);
        }

        return $this->returnJson([
            'id' => $savedUser->getId(),
            'name' => $savedUser->getName(),
            'username' => $savedUser->getUsername()
        ], 201);
    }

    // Update an existing user
    public function update(): ResponseInterface
    {
        $id = $this->request->getData('id') ?? 0;
        $userDatas = $this->request->getAllDatas();

        // Check referrer (CSRF protection)
        if (!$this->request->isRefererValid()) {
            return $this->returnError(403);
        }

        // Find user
        $user = $this->repo->find($id);
        if (!$user) {
            return $this->returnError(404);
        }

        // Update properties
        Hydrator::hydrate($user, $userDatas);

        // Save changes
        $result = $this->repo->save($user);
        if (!$result) {
            return $this->returnError(500);
        }

        return $this->returnJson(null, 204);
    }

    // Delete a user
    public function delete(): ResponseInterface
    {
        $id = $this->request->getData('id') ?? 0;

        $user = $this->repo->find($id);
        if (!$user) {
            return $this->returnError(404);
        }

        $this->repo->delete($user);
        return $this->returnJson(null, 204);
    }
}
```

---

## Testing

### Example: RequestTest.php

```php
use App\Kernel\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testInitRequest(): void
    {
        Request::resetInstance();
        $request = Request::initInstance([], [], [], [], [], [], []);
        
        $this->assertIsObject($request);
        $this->assertInstanceOf(Request::class, $request);
    }

    public function testRequestHasDatas(): void
    {
        Request::resetInstance();
        
        $get = ['page' => 1];
        $post = ['name' => 'John', 'email' => 'john@example.com'];
        
        $request = Request::initInstance([], [], $get, $post, [], [], []);
        
        $this->assertEquals('John', $request->getData('name'));
        $this->assertEquals('john@example.com', $request->getData('email'));
        $this->assertEquals(1, $request->getData('page'));
    }

    public function testIdExtractionFromUrl(): void
    {
        Request::resetInstance();
        
        $server = ['REQUEST_URI' => '/user/123'];
        $request = Request::initInstance($server, [], [], [], [], [], []);
        
        $this->assertEquals(123, $request->getData('id'));
        $this->assertEquals('user', $request->getURI());
    }

    public function testSetRequestData(): void
    {
        Request::resetInstance();
        $request = Request::initInstance([], [], [], [], [], [], []);
        
        $request->setData('custom', 'value');
        
        $this->assertEquals('value', $request->getData('custom'));
    }

    public function testRequestHasFiles(): void
    {
        Request::resetInstance();
        
        $files = [
            'document' => [
                'name' => 'report.pdf',
                'type' => 'application/pdf',
                'tmp_name' => '/tmp/phpXYZ',
                'error' => 0,
                'size' => 12345
            ]
        ];
        
        $request = Request::initInstance([], [], [], [], $files, [], []);
        
        $fileArray = $request->getFile('document');
        $this->assertNotNull($fileArray);
        $this->assertCount(1, $fileArray);
    }

    public function testCustomParameters(): void
    {
        Request::resetInstance();
        $request = Request::initInstance([], [], [], [], [], [], []);
        
        $request->addParam('action', 'delete');
        $request->addParam('resource', 'user_123');
        
        $this->assertEquals('delete', $request->getParam('action'));
        $this->assertEquals('user_123', $request->getParam('resource'));
    }

    public function testSessionValues(): void
    {
        Request::resetInstance();
        $request = Request::initInstance([], [], [], [], [], [], []);
        
        $request->setSessionValue('theme', 'dark');
        
        $this->assertEquals('dark', $request->getSessionValue('theme'));
    }

    public function testHeaders(): void
    {
        Request::resetInstance();
        
        $headers = ['Content-Type' => 'application/json'];
        $request = Request::initInstance([], [], [], [], [], [], $headers);
        
        $this->assertEquals('application/json', $request->getHeaders('Content-Type'));
    }
}
```

### Example: Request to Entity Test

```php
use App\Kernel\Request;
use App\Kernel\Connector\Hydrator;
use PHPUnit\Framework\TestCase;

class Request2EntityTest extends TestCase
{
    public function testEntityFromRequest(): void
    {
        Request::resetInstance();
        
        // Simulate POST request
        $post = [
            'name' => 'Doe',
            'firstname' => 'John',
            'age' => 30
        ];
        
        $request = Request::initInstance([], [], [], $post, [], [], []);
        
        // Convert request data to entity
        $entity = Hydrator::hydrate(new User(), $request->getAllDatas());
        
        $this->assertInstanceOf(User::class, $entity);
        $this->assertEquals('John', $entity->getFirstname());
        $this->assertEquals('Doe', $entity->getName());
        $this->assertEquals(30, $entity->getAge());
    }
}
```

---

## Best Practices

### 1. Always Check Data Exists

```php
// ✓ GOOD - Use null coalescing operator
$id = $request->getData('id') ?? null;
if ($id) {
    // Process
}

// ✗ BAD - Assume data exists
$id = $request->getData('id');  // Might be null
```

### 2. Validate User Input

```php
// ✓ GOOD
$email = $request->getData('email');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return $this->returnError(422);
}

// ✗ BAD - Use data without validation
$email = $request->getData('email');
// Use directly without validation
```

### 3. Use Hydrator for Entity Population

```php
// ✓ GOOD
$user = Hydrator::hydrate(new User(), $request->getAllDatas());

// ✗ BAD - Manual property setting
$user = new User();
$user->setName($request->getData('name'));
$user->setEmail($request->getData('email'));
// ... many lines
```

### 4. Check Authentication Before Operations

```php
// ✓ GOOD
if (!$request->isConnected()) {
    return $this->returnError(401);
}

// Continue with authenticated user
$user = $request->getUser();

// ✗ BAD - Assume user is authenticated
$user = $request->getUser();
// Might be null and cause error
```

### 5. Validate Referrer for Sensitive Operations

```php
// ✓ GOOD - For state-changing operations
public function delete(): ResponseInterface
{
    if (!$request->isRefererValid()) {
        return $this->returnError(403);
    }
    // Delete logic
}

// ✓ GOOD - For read operations (no need to validate)
public function get(): ResponseInterface
{
    // No CSRF risk for read
    // Return data
}
```


### 6. Separate Concerns

```php
// ✓ GOOD - Extract request handling from business logic
class UserController extends AbstractController
{
    public function get(): ResponseInterface
    {
        $id = (int)$this->request->getData('id');
        $user = $this->getUserById($id);  // Separate method
        return $this->returnJson($user);
    }

    private function getUserById(int $id): ?User
    {
        return $this->repo->find($id);
    }
}
```

## Summary

| Feature | Method | Parameters | Returns |
|---------|--------|-----------|---------|
| **Initialization** | `initInstance()` | server, datas, get, post, files, session, headers, cookies | `Request` |
| **Get Instance** | `getRequestInstance()` | — | `Request` |
| **HTTP Method** | `getMethod()` | — | `string` |
| **All Data** | `getAllDatas()` | — | `array` |
| **Single Data** | `getData(name)` | name | `mixed` |
| **Set Data** | `setData(key, value)` | key, value | `void` |
| **URI (with Routes)** | `getURI(routes)` | routes array | `string` (or `null`) |
| **URI (raw)** | `getURI()` | — | `string` (or `null`) |
| **Files** | `getFiles()` | — | `array` |
| **File** | `getFile(name)` | name | `array\|null` |
| **Headers** | `getHeaders(name)` | name | `string\|null` |
| **Cookies** | `getCookie(name)` | name | `string\|null` |
| **Session Value** | `getSessionValue(name)` | name | `mixed` |
| **Add Parameter** | `addParam(name, value)` | name, value | `$this` |
| **Get Parameter** | `getParam(name)` | name | `mixed` |

---

## Request and Routing Integration

The Request class is tightly integrated with the `RouteCompiler` and `Kernel` to provide a cohesive routing system:

### Kernel Integration

In the Kernel's constructor, routes are retrieved and passed to the Request:

```php
// From Kernel.php
$routes = Router::getRoutes();  // Get all defined routes
$request = Request::initInstance($_SERVER, $datas, $_GET, $_POST, $_FILES, $_SESSION, $headers, $_COOKIE);

// Most important line: Pass routes to getURI()
$routeCall = $request->getURI($routes);

// $routeCall is now the matched route pattern like 'user/{id}'
// Route parameters are automatically extracted into Request data
```

### Request Lifecycle

1. **Request Creation**: `Request::initInstance()` with raw server data
2. **Route Matching**: `request->getURI($routes)` using RouteCompiler
3. **Parameter Extraction**: Parameters extracted from URL into data
4. **Route Resolution**: Kernel uses returned route to find controller
5. **Data Access**: Controller accesses parameters via `$request->getData()`

### Typical Flow Example

```php
// URL: /user/42/post/7

// 1. Kernel creates request and routes
$request = Request::initInstance($_SERVER, ...);
$routes = Router::getRoutes();  // Contains 'user/{id}/post/{postId}'

// 2. Request matches route
$uri = $request->getURI($routes);
// Result: 'user/{id}/post/{postId}'

// 3. Parameters extracted automatically
$userId = $request->getData('id');          // '42'
$postId = $request->getData('postId');      // '7'

// 4. Kernel resolves controller from $routes[$uri]
[$controller, $method] = $routes[$uri][$httpMethod];

// 5. Controller receives fully-initialized request
class UserController extends AbstractController
{
    public function getPost(): ResponseInterface
    {
        $userId = $this->request->getData('id');      // Works!
        $postId = $this->request->getData('postId');  // Works!
        // ... handle request
    }
}
```

---

## Related Documentation

- [Router Configuration](./router.md) - Define your application routes
- [Kernel](./kernel.md) - Application engine that manages request lifecycle
- [Controller](./controller.md) - Build controllers that work with Request
- [Route Compilation Details](./router.md#route-compiler) - Deep dive into pattern matching

| **Session** | `getSessionValue(name)` | `mixed` |
| **User** | `getUser()` | `User\|null` |
| **Connected** | `isConnected()` | `bool` |
| **Referrer Valid** | `isRefererValid()` | `bool` |
| **Custom Param** | `addParam(name, value)` | `self` |
| **Get Param** | `getParam(name)` | `mixed` |


# Request Class Documentation

## Overview

The `Request` class is a singleton that encapsulates all HTTP request data and provides a unified interface to access GET parameters, POST data, files, headers, cookies, sessions, and user information. It implements the Singleton pattern to ensure a single instance throughout the application lifecycle.

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

### Getting the URI

```php
$uri = $request->getURI();
// URL: /user/profile/1
// Result: 'user/profile'
```

### Automatic ID Extraction

The Request class automatically extracts numeric IDs from the end of the URL path:

```php
$server = ['REQUEST_URI' => '/user/123'];
$request = Request::initInstance($server, [], [], [], [], [], []);

$id = $request->getData('id');  // 123
$uri = $request->getURI();      // 'user'
```

### ID Override

If an explicit `id` is provided in the data, it takes precedence:

```php
$request = Request::initInstance(
    ['REQUEST_URI' => '/user/123'],
    [],
    ['id' => 456],  // GET parameter
    [],
    [], [], []
);

$id = $request->getData('id');  // 456 (from GET, not 123)
```

### URI Sanitization

URIs are sanitized using `FILTER_SANITIZE_URL` to prevent injection attacks:

```php
$server = ['REQUEST_URI' => '/user/<script>alert("xss")</script>'];
$request = Request::initInstance($server, [], [], [], [], [], []);
// The URI is sanitized automatically
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

---

## Summary

| Feature | Method | Returns |
|---------|--------|---------|
| **Initialization** | `initInstance()` | `Request` |
| **Get Instance** | `getRequestInstance()` | `Request` |
| **HTTP Method** | `getMethod()` | `string` |
| **All Data** | `getAllDatas()` | `array` |
| **Single Data** | `getData(name)` | `mixed` |
| **Set Data** | `setData(key, value)` | `void` |
| **URI** | `getURI()` | `string` |
| **Files** | `getFiles()` | `array` |
| **File** | `getFile(name)` | `array\|null` |
| **Headers** | `getHeaders(name)` | `string\|null` |
| **Cookies** | `getCookie(name)` | `string\|null` |
| **Session** | `getSessionValue(name)` | `mixed` |
| **User** | `getUser()` | `User\|null` |
| **Connected** | `isConnected()` | `bool` |
| **Referrer Valid** | `isRefererValid()` | `bool` |
| **Custom Param** | `addParam(name, value)` | `self` |
| **Get Param** | `getParam(name)` | `mixed` |


# Authentication System Documentation

## Overview

The smallMVC authentication system provides a flexible, multi-strategy authentication framework supporting three primary authentication methods:

1. **JWT Bearer Tokens** - Stateless, token-based authentication for APIs
2. **HTTP Basic Authentication** - Credential-based authentication using HTTP Authorization header
3. **Session-Based Authentication** - Session-stored user identification

All authentication strategies implement the `AuthenticationInterface` contract, allowing interchangeable authentication methods. The `AuthManagerMiddleware` automatically detects and applies the appropriate authentication strategy based on the incoming request.

### Location
`App\Kernel\Middleware\Security\`

---

## AuthenticationInterface

### Description

`AuthenticationInterface` is the contract that all authentication middlewares must implement. It ensures consistent authentication behavior across different strategies.

### Location
`App\Kernel\Interfaces\AuthenticationInterface`

### Methods

```php
public function isAuth(): bool;
public function getUser(): ?User;
```

| Method | Purpose |
|--------|---------|
| `isAuth(): bool` | Returns true if the user is authenticated, false otherwise |
| `getUser(): ?User` | Returns the authenticated User object, or null if not authenticated |

### Implementation Example

```php
class MyAuthMiddleware implements AuthenticationInterface
{
    private ?User $user = null;

    public function isAuth(): bool
    {
        return $this->user !== null;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}
```

---

## AuthBearerMiddleware

### Description

Implements JWT Bearer token authentication for API requests. Validates Bearer tokens in the `Authorization` header and retrieves the authenticated user.

### Location
`App\Kernel\Middleware\Security\AuthBearerMiddleware`

### Constructor

```php
public function __construct(private UserRepository $repo)
```

**Parameters:**
- `$repo` (UserRepository): Repository for fetching user data from database

**Behavior:**
1. Extracts `Authorization` header from request
2. Parses Bearer token (format: `Bearer <token>`)
3. Calls `isAuth()` to validate and authenticate

### Methods

#### `isAuth(): bool`

Validates the Bearer token through comprehensive checks.

**Validation Steps:**
1. Checks if token is present
2. Validates token format (JWT format)
3. Checks token expiration
4. Fetches user from database
5. Verifies stored token matches submitted token
6. Validates token signature using secret

**Returns:** `true` if all validations pass, `false` otherwise

```php
$middleware = new AuthBearerMiddleware($userRepo);
if ($middleware->isAuth()) {
    $user = $middleware->getUser();
}
```

#### `getUser(): ?User`

Returns authenticated user or null if not authenticated.

### Usage Example

#### API Authentication Flow

```php
<?php

namespace App\Controllers;

use App\Kernel\Middleware\Security\AuthBearerMiddleware;
use App\Security\UserRepository;

class ApiController
{
    public function protectedAction()
    {
        $userRepo = new UserRepository();
        $auth = new AuthBearerMiddleware($userRepo);

        if (!$auth->isAuth()) {
            return new ClientErrorResponse(401);
        }

        $user = $auth->getUser();
        
        // Use authenticated user
        return new JsonResponse([
            'message' => 'Hello ' . $user->getFirstname(),
            'userId' => $user->getId(),
            'roles' => $user->getRoles()
        ]);
    }
}
```

#### Request with Bearer Token

```
GET /api/users HTTP/1.1
Host: example.com
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VySWQiOjEsInJvbGUiOlsiVVNFUiJdfQ...
```

### Test Cases

#### Test 1: No Token

```php
public function testNoToken(): void
{
    // Request without Authorization header
    $request = Request::initInstance([], [], [], [], [], [], []);
    $bearerAuth = new AuthBearerMiddleware($userRepo);
    
    $this->assertNull($bearerAuth->getUser());
    $this->assertFalse($bearerAuth->isAuth());
}
```

#### Test 2: Invalid Token Format

```php
public function testWithBadToken(): void
{
    $headers = [
        'Authorization' => 'Bearer INVALID_TOKEN'
    ];
    $request = Request::initInstance([], [], [], [], [], [], $headers);
    $bearerAuth = new AuthBearerMiddleware($userRepo);
    
    $this->assertNull($bearerAuth->getUser());
    $this->assertFalse($bearerAuth->isAuth());
}
```

#### Test 3: Valid Token

```php
public function testWithValidToken(): void
{
    $jwt = new JwtToken();
    $token = $jwt->createToken(
        ['userId' => 1, 'userName' => 'john'],
        'secret',
        86400
    );
    
    // Mock database response
    $connector->method('fetchQuery')->willReturn([[
        'id' => 1,
        'firstname' => 'John',
        'token' => $token,
        'roles' => json_encode(['USER'])
    ]]);
    
    $bearerAuth = new AuthBearerMiddleware($userRepo);
    
    $this->assertTrue($bearerAuth->isAuth());
    $this->assertInstanceOf(User::class, $bearerAuth->getUser());
}
```

---

## AuthHttpMiddleware

### Description

Implements HTTP Basic Authentication. Retrieves username and password from the HTTP `Authorization: Basic` header and validates them against the database.

### Location
`App\Kernel\Middleware\Security\AuthHttpMiddleware`

### Constructor

```php
public function __construct(private RepositoryInterface $repo)
```

**Parameters:**
- `$repo` (RepositoryInterface): User repository for credential validation

**Behavior:**
1. Extracts username and password from HTTP server variables (`PHP_AUTH_USER`, `PHP_AUTH_PW`)
2. Queries database for user with matching credentials
3. Sets `$user` if credentials are valid

### Methods

#### `isAuth(): bool`

Returns true if user was successfully authenticated with valid credentials.

```php
if ($httpAuth->isAuth()) {
    $user = $httpAuth->getUser();
}
```

#### `getUser(): ?User`

Returns authenticated user or null if not authenticated.

### HTTP Basic Auth Format

Clients must send credentials in Base64 encoding:

```
GET /api/resource HTTP/1.1
Host: example.com
Authorization: Basic dXNlcm5hbWU6cGFzc3dvcmQ=
```

Where `dXNlcm5hbWU6cGFzc3dvcmQ=` is Base64 encoding of `username:password`

### Usage Example

```php
<?php

use App\Kernel\Middleware\Security\AuthHttpMiddleware;
use App\Security\UserRepository;

$userRepo = new UserRepository();
$auth = new AuthHttpMiddleware($userRepo);

if ($auth->isAuth()) {
    $user = $auth->getUser();
    echo "Authenticated as: " . $user->getFirstname();
} else {
    // Send 401 response
    http_response_code(401);
    header('WWW-Authenticate: Basic realm="API"');
    exit("Invalid credentials");
}
```

### Test Cases

#### Test 1: No Credentials

```php
public function testNoUserInSession(): void
{
    // No PHP_AUTH_USER or PHP_AUTH_PW
    $request = Request::initInstance([], [], [], [], [], [], []);
    $httpAuth = new AuthHttpMiddleware($userRepo);
    
    $this->assertNull($httpAuth->getUser());
    $this->assertFalse($httpAuth->isAuth());
}
```

#### Test 2: Invalid Credentials

```php
public function testWithUnknownId(): void
{
    // User not found in database
    $connector->method('fetchQuery')->willReturn([]);
    
    $server = [
        'PHP_AUTH_USER' => 'unknown',
        'PHP_AUTH_PW' => 'wrongpass'
    ];
    $request = Request::initInstance($server, [], [], [], [], [], []);
    $httpAuth = new AuthHttpMiddleware($userRepo);
    
    $this->assertNull($httpAuth->getUser());
    $this->assertFalse($httpAuth->isAuth());
}
```

#### Test 3: Valid Credentials

```php
public function testWithValidCredentials(): void
{
    // User found with matching password
    $connector->method('fetchQuery')->willReturn([[
        'id' => 1,
        'firstname' => 'John',
        'password' => '$2y$12$...',  // hashed password
        'roles' => json_encode(['USER'])
    ]]);
    
    $server = [
        'PHP_AUTH_USER' => 'johndoe',
        'PHP_AUTH_PW' => 'password123'
    ];
    $request = Request::initInstance($server, [], [], [], [], [], []);
    $httpAuth = new AuthHttpMiddleware($userRepo);
    
    $this->assertTrue($httpAuth->isAuth());
    $this->assertEquals("John", $httpAuth->getUser()->getFirstname());
}
```

---

## SessionAuthMiddleware

### Description

Implements session-based authentication. Retrieves user ID from PHP session and fetches corresponding user from database.

### Location
`App\Kernel\Middleware\Security\SessionAuthMiddleware`

### Constructor

```php
public function __construct(private RepositoryInterface $repo)
```

**Parameters:**
- `$repo` (RepositoryInterface): User repository for fetching session user

**Behavior:**
1. Retrieves `userId` from session (`$_SESSION['userId']`)
2. Queries database for user with that ID
3. Sets `$user` if user is found

### Methods

#### `isAuth(): bool`

Returns true if a valid user is associated with the session.

#### `getUser(): ?User`

Returns the session-associated user or null.

### Session Format

Session must contain a `userId` key:

```php
$_SESSION['userId'] = 1;  // User ID from database
```

### Usage Example

```php
<?php

use App\Kernel\Middleware\Security\SessionAuthMiddleware;
use App\Security\UserRepository;

// After user login
session_start();
$userId = authenticate_user($username, $password);
$_SESSION['userId'] = $userId;

// On subsequent requests
$userRepo = new UserRepository();
$auth = new SessionAuthMiddleware($userRepo);

if ($auth->isAuth()) {
    $user = $auth->getUser();
    echo "Welcome back, " . $user->getFirstname();
}
```

### Login Flow Example

```php
public function login($username, $password)
{
    $repo = new UserRepository();
    
    // Find user and verify password
    $user = $repo->findByUsername($username);
    
    if ($user && password_verify($password, $user->getPassword())) {
        // Start session and store user ID
        session_start();
        $_SESSION['userId'] = $user->getId();
        
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => 'Invalid credentials'];
}
```

### Test Cases

#### Test 1: No Session User

```php
public function testNoUserInSession(): void
{
    // Session without userId
    $session = ['other_key' => 'value'];
    $request = Request::initInstance([], [], [], [], [], $session, []);
    $sessionAuth = new SessionAuthMiddleware($userRepo);
    
    $this->assertNull($sessionAuth->getUser());
    $this->assertFalse($sessionAuth->isAuth());
}
```

#### Test 2: Unknown User ID

```php
public function testWithUnknownId(): void
{
    // UserId not found in database
    $connector->method('fetchQuery')->willReturn([]);
    
    $session = ['userId' => 999];
    $request = Request::initInstance([], [], [], [], [], $session, []);
    $sessionAuth = new SessionAuthMiddleware($userRepo);
    
    $this->assertNull($sessionAuth->getUser());
    $this->assertFalse($sessionAuth->isAuth());
}
```

#### Test 3: Valid Session User

```php
public function testWithValidSessionUser(): void
{
    // User found in database
    $connector->method('fetchQuery')->willReturn([[
        'id' => 1,
        'firstname' => 'John',
        'roles' => json_encode(['USER'])
    ]]);
    
    $session = ['userId' => 1];
    $request = Request::initInstance([], [], [], [], [], $session, []);
    $sessionAuth = new SessionAuthMiddleware($userRepo);
    
    $this->assertTrue($sessionAuth->isAuth());
    $this->assertEquals(1, $sessionAuth->getUser()->getId());
}
```

---

## AuthManagerMiddleware

### Description

Middleware that automatically detects the authentication type and applies the appropriate authentication strategy. Acts as a dispatcher for authentication methods based on request data.

### Location
`App\Kernel\Middleware\Security\AuthManagerMiddleware`

### Purpose

- Detects which authentication method is being used
- Instantiates corresponding authentication middleware
- Sets the authenticated user in the request object
- Ensures only one authentication method is applied

### Methods

#### `execute(StoppableEventInterface $event): void`

Executes the authentication detection and application logic.

**Parameters:**
- `$event` (StoppableEventInterface): PSR-14 event object

**Behavior:**

1. **Check for Bearer Token**: If `Authorization` header exists
   ```
   Authorization: Bearer <token>
   ```
   → Applies `AuthBearerMiddleware`

2. **Check for Session**: If `userId` exists in session
   ```php
   $_SESSION['userId']
   ```
   → Applies `SessionAuthMiddleware`

3. **Check for HTTP Basic**: If `PHP_AUTH_USER` and `PHP_AUTH_PW` server vars exist
   ```
   Authorization: Basic <credentials>
   ```
   → Applies `AuthHttpMiddleware`

4. **Set User**: Sets authenticated user in request object
5. **Stop Event Propagation**: Prevents other listeners from processing

### Authentication Priority

The middleware checks authentication methods in this order:
1. Bearer Token (JWT)
2. Session
3. HTTP Basic Auth

If multiple methods are present, the first one found (Bearer) takes precedence.

### Usage Example

```php
<?php

// In kernel/middleware initialization
use App\Kernel\Middleware\Security\AuthManagerMiddleware;

$dispatcher = new EventDispatcher();
$dispatcher->addEventListener('beforeController', new AuthManagerMiddleware());

// Now in controllers, access authenticated user:
$request = Request::getRequestInstance();
$user = $request->getUser();

if ($user) {
    echo "Authenticated user: " . $user->getFirstname();
} else {
    echo "No authentication found";
}
```

### Integration with Request

```php
// The AuthManagerMiddleware calls:
$request->setUser($authUser);

// Later in controller:
$user = $request->getUser();  // Authenticated user or null
```

---

## Complete Authentication Example

### Login Controller

```php
<?php

namespace App\Controllers;

use App\Kernel\Responses\JsonResponse;
use App\Kernel\Responses\ClientErrorResponse;
use App\Kernel\Security\CreateJwtAuth;
use App\Security\UserRepository;

class AuthController
{
    public function login()
    {
        $request = Request::getRequestInstance();
        $username = $request->getPost('username');
        $password = $request->getPost('password');

        $repo = new UserRepository();
        $user = $repo->findByUserNameCredentials($username, $password);

        if (!$user) {
            $response = new ClientErrorResponse(401);
            $response->setBody(json_encode(['error' => 'Invalid credentials']));
            return $response->send();
        }

        // Generate JWT token
        $token = CreateJwtAuth::createToken(
            $user->getId(),
            $user->getRoles(),
            $user->getFirstname(),
            $user->getLastname(),
            86400  // 1 day validity
        );

        // Store token in database
        $user->setToken($token);
        $repo->update($user);

        $response = new JsonResponse(200);
        $response->setBody([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'roles' => $user->getRoles()
            ]
        ]);

        return $response->send();
    }
}
```

### Protected API Endpoint

```php
public function getProfile()
{
    $request = Request::getRequestInstance();
    $user = $request->getUser();

    if (!$user) {
        $response = new ClientErrorResponse(401);
        return $response->send();
    }

    $response = new JsonResponse(200);
    $response->setBody([
        'id' => $user->getId(),
        'firstname' => $user->getFirstname(),
        'lastname' => $user->getLastname(),
        'roles' => $user->getRoles(),
        'email' => $user->getEmail()
    ]);

    return $response->send();
}
```

### Client-Side Usage

#### With Bearer Token (API)

```javascript
// Set token from login response
const token = loginResponse.token;

// Make authenticated request
fetch('/api/profile', {
    method: 'GET',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => console.log(data));
```

#### With Session (Web)

```php
// Login form submission
if ($_POST['login'] ?? false) {
    session_start();
    $_SESSION['userId'] = authenticate($username, $password);
    redirect('/dashboard');
}

// Protected page
session_start();
if (!isset($_SESSION['userId'])) {
    redirect('/login');
}
```

#### With HTTP Basic Auth (API)

```bash
curl -u username:password http://example.com/api/profile
# Or
curl -H "Authorization: Basic dXNlcm5hbWU6cGFzc3dvcmQ=" http://example.com/api/profile
```

---

## Security Best Practices

### 1. Token Storage (JWT/Bearer)

**✅ Good:**
```php
// Store in HTTP-only cookie (not accessible by JavaScript)
setcookie('auth_token', $token, [
    'httponly' => true,
    'secure' => true,      // HTTPS only
    'samesite' => 'Strict'
]);
```

**❌ Bad:**
```php
// Don't store in localStorage (vulnerable to XSS)
// localStorage.setItem('token', token);
```

### 2. Password Management

**✅ Good:**
```php
// Use bcrypt for password hashing
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Verify password
if (password_verify($inputPassword, $hashedPassword)) {
    // Valid
}
```

**❌ Bad:**
```php
// Never use plain text or weak hashing
// user.password = $password;  // NO!
// user.password = md5($password);  // NO!
```

### 3. Token Validity

**✅ Good:**
```php
// Short-lived tokens with refresh mechanism
$token = CreateJwtAuth::createToken(
    $userId,
    $roles,
    $firstname,
    $lastname,
    3600  // 1 hour
);

// Use refresh token for renewal
$refreshToken = CreateJwtAuth::createToken(..., 604800);  // 1 week
```

**❌ Bad:**
```php
// Very long-lived tokens
$token = CreateJwtAuth::createToken(..., 31536000);  // 1 year - risky!
```

### 4. HTTPS Enforcement

**✅ Good:**
```php
// Force HTTPS in production
if ($_SERVER['HTTPS'] !== 'on' && php_sapi_name() !== 'cli-server') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
```

### 5. CORS Configuration (for API)

**✅ Good:**
```php
// Restrict CORS to trusted domains
header('Access-Control-Allow-Origin: https://trusted-domain.com');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

### 6. Logout/Token Revocation

**✅ Good:**
```php
public function logout()
{
    $user = $request->getUser();
    
    // Clear token from database
    $user->setToken(null);
    $repo->update($user);
    
    // Clear session
    session_destroy();
    
    // Clear cookie
    setcookie('auth_token', '', time() - 3600);
}
```

---

## Summary Table

| Method | Header Format | Storage | Use Case | Stateless |
|--------|---------------|---------|----------|-----------|
| **Bearer (JWT)** | `Authorization: Bearer <token>` | Client | APIs, Mobile | Yes |
| **HTTP Basic** | `Authorization: Basic <base64>` | Client | Simple Auth | Yes |
| **Session** | Cookie `PHPSESSID` | Server | Web Apps | No |

| Feature | Bearer | HTTP Basic | Session |
|---------|--------|-----------|---------|
| Token Expiration | ✅ Yes | ❌ No | ✅ Yes |
| Refresh Support | ✅ Yes | ❌ No | ✅ Yes |
| API Friendly | ✅ Yes | ✅ Yes | ❌ No |
| Scalable | ✅ Yes | ✅ Yes | ❌ Limited |
| Web Browser Support | ⚠️ Custom | ✅ Native | ✅ Native |

---

## Related Classes and Interfaces

- `User`: User entity class
- `UserRepository`: User data persistence
- `Request`: Request object with user storage
- `AuthenticationInterface`: Base contract for auth strategies
- `JwtToken`: JWT token handling
- `CreateJwtAuth`: JWT creation helper

---

## Related Documentation

- [Response System Documentation](Response.md)
- [Request Object Documentation](RequestObject.md)
- [User Entity Documentation](User.md)

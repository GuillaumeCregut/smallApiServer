# CreateJwtAuth Documentation

## Overview

The `CreateJwtAuth` class is a simplified wrapper around the `JwtToken` class that provides an easy, minimal-configuration way to create authentication tokens for logged-in users. It abstracts away the complexity of JWT token creation and automatically uses the application's secret key from environment configuration.

## Purpose

The CreateJwtAuth class serves to:
- **Simplify token creation** for user authentication
- **Standardize token format** across the application
- **Minimize configuration** required for token generation
- **Encapsulate user information** in the token payload
- **Auto-load secret key** from environment variables
- **Reduce boilerplate code** for authentication flows

## Benefits Over Direct JwtToken Usage

While you could use `JwtToken` directly for authentication, `CreateJwtAuth` provides:

| Feature | CreateJwtAuth | JwtToken |
|---------|---------------|----------|
| Automatic secret loading | ✓ | ✗ |
| User-focused API | ✓ | ✗ |
| Pre-configured payload | ✓ | ✗ |
| Minimal parameters | ✓ | ✗ |
| Token creation only | ✓ | ✓ |

## Class Structure

### Location

```
App\Kernel\Security\CreateJwtAuth
```

### Namespace

```php
namespace App\Kernel\Security;
```

### Dependencies

- `JwtToken` - For token creation and HMAC signing
- `GetEnvDatas` - For retrieving the secret key from environment

## Method Reference

### createToken() (Static)

Creates an authentication JWT token for a logged-in user with minimal required parameters.

```php
public static function createToken(
    int $userId,
    array $role,
    ?string $firstname,
    ?string $lastname,
    ?int $validity = 86400
): string
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$userId` | int | Required | Unique identifier of the user |
| `$role` | array | Required | Array of user roles (e.g., ['admin', 'user']) |
| `$firstname` | string\|null | Required | User's first name (can be null) |
| `$lastname` | string\|null | Required | User's last name (can be null) |
| `$validity` | int\|null | 86400 | Token validity in seconds (24 hours default) |

#### Return Value

Returns a signed JWT token string ready for use in the application.

Format: `header.payload.signature`

#### Token Payload Structure

The created token contains the following payload:

```json
{
  "userId": 123,
  "role": ["admin", "user"],
  "firstname": "John",
  "lastname": "Doe",
  "iat": 1769953083,
  "exp": 1770039483
}
```

#### Environment Configuration

The secret key is automatically loaded from the application's environment variables:

```bash
# .env file
secret=your-secret-key-here
```

The secret must be set in environment variables or the method will throw an exception.

## Basic Usage

### Simple Token Creation

```php
<?php

use App\Kernel\Security\CreateJwtAuth;

// After user login/authentication
$userId = 123;
$roles = ['user'];
$firstName = 'John';
$lastName = 'Doe';

// Create authentication token
$token = CreateJwtAuth::createToken(
    $userId,
    $roles,
    $firstName,
    $lastName
);

// Use token (e.g., return to client, set in cookie, session, etc.)
echo $token;
```

### With Custom Validity Period

```php
<?php

use App\Kernel\Security\CreateJwtAuth;

// 1 hour token
$token = CreateJwtAuth::createToken(
    userId: 456,
    role: ['admin'],
    firstname: 'Jane',
    lastname: 'Smith',
    validity: 3600  // 1 hour
);

// 7 days token
$token = CreateJwtAuth::createToken(
    userId: 789,
    role: ['user', 'moderator'],
    firstname: 'Bob',
    lastname: 'Johnson',
    validity: 604800  // 7 days
);
```

### With Null Names

```php
<?php

use App\Kernel\Security\CreateJwtAuth;

// User without first/last name
$token = CreateJwtAuth::createToken(
    userId: 999,
    role: ['guest'],
    firstname: null,
    lastname: null
);
```

### Multiple Roles

```php
<?php

use App\Kernel\Security\CreateJwtAuth;

// User with multiple roles
$token = CreateJwtAuth::createToken(
    userId: 555,
    role: ['admin', 'moderator', 'editor'],
    firstname: 'Alice',
    lastname: 'Williams'
);
```

## Practical Examples

### Example 1: Login Controller

```php
<?php

namespace App\Controllers;

use App\Kernel\AbstractController;
use App\Kernel\AbstractResponse;
use App\Kernel\Request;
use App\Kernel\Responses\JsonResponse;
use App\Security\User;
use App\Kernel\Security\CreateJwtAuth;

class LoginController extends AbstractController
{
    public function login(): AbstractResponse
    {
        $request = Request::getRequestInstance();
        
        // Get credentials from request
        $email = $request->getData()['email'] ?? null;
        $password = $request->getData()['password'] ?? null;
        
        if (!$email || !$password) {
            return new JsonResponse(
                ['error' => 'Email and password required'],
                400
            );
        }
       
        // Find user in database
        $user = $userRepository->findOne($id);
        if (!$user || !password_verify($password, $user->getPassword())) {
            return new JsonResponse(
                ['error' => 'Invalid credentials'],
                401
            );
        }
        
        // Create authentication token
        $token = CreateJwtAuth::createToken(
            userId: $user->getId(),
            role: $user->getRoles(),
            firstname: $user->getFirstName(),
            lastname: $user->getLastName()
        );
        
        return new JsonResponse([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getFirstName() . ' ' . $user->getLastName()
            ]
        ]);
    }
}
```

### Example 2: Authentication Service

```php
<?php

namespace App\Services;

use App\Kernel\Security\CreateJwtAuth;
use App\Kernel\Security\JwtToken;
use App\Models\User;

class AuthenticationService
{
    private string $secret;

    public function __construct()
    {
        $env = new \App\Kernel\GetEnvDatas();
        $this->secret = $env->get('secret');
    }

    public function authenticateUser(string $email, string $password): ?array
    {
        // Find user
        $user = $userRepository->findOneBy($email);
        
        if (!$user) {
            return null;
        }
        
        // Verify password
        if (!password_verify($password, $user->getPassword())) {
            return null;
        }
        
        // Create token
        $token = CreateJwtAuth::createToken(
            userId: $user->getId(),
            role: $user->getRoles(),
            firstname: $user->getFirstName(),
            lastname: $user->getLastName(),
            validity: 86400  // 24 hours
        );
        
        return [
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFirstName() . ' ' . $user->getLastName(),
                'roles' => $user->getRoles()
            ]
        ];
    }

    public function validateToken(string $token): ?array
    {
        $jwt = new JwtToken();
        
        // Check format
        if (!JwtToken::checkFormat($token)) {
            return null;
        }
        
        // Check signature
        if (!$jwt->checkToken($token, $this->secret)) {
            return null;
        }
        
        // Check expiration
        if ($jwt->isExpired($token)) {
            return null;
        }
        
        // Return user data
        return $jwt->getPayload();
    }
}
```

### Example 3: API Request Handler

```php
<?php

namespace App\Middleware;

use App\Kernel\Security\CreateJwtAuth;
use App\Kernel\Security\JwtToken;
use App\Kernel\Request;

class AuthenticationMiddleware
{
    private string $secret;

    public function __construct()
    {
        $env = new \App\Kernel\GetEnvDatas();
        $this->secret = $env->get('secret');
    }

    public function handle(Request $request): ?array
    {
        // Get token from header
        $token = $request->getHeader('Authorization');
        
        if (!$token) {
            return null;
        }
        
        // Remove "Bearer " prefix if present
        if (str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }
        
        // Validate token
        $jwt = new JwtToken();
        
        if (!JwtToken::checkFormat($token)) {
            return null;
        }
        
        if (!$jwt->checkToken($token, $this->secret)) {
            return null;
        }
        
        if ($jwt->isExpired($token)) {
            return null;
        }
        
        // Return authenticated user data
        return $jwt->getPayload();
    }
}
```

### Example 4: Token Refresh Handler

```php
<?php

namespace App\Services;

use App\Kernel\Security\CreateJwtAuth;
use App\Kernel\Security\JwtToken;
use App\Models\User;

class TokenRefreshService
{
    private string $secret;

    public function __construct()
    {
        $env = new \App\Kernel\GetEnvDatas();
        $this->secret = $env->get('secret');
    }

    public function refreshToken(string $expiredToken): ?string
    {
        // Extract payload from expired token
        $jwt = new JwtToken();
        $userRepository = new UserRepository();
        try {
            $payload = $jwt->extractPayload($expiredToken);
        } catch (\Exception $e) {
            return null;
        }
        
        // Verify user still exists
        $user = $userRepository->findOne($payload['userId']);
        if (!$user) {
            return null;
        }
        
        // Create new token with same user data
        return CreateJwtAuth::createToken(
            userId: $user->getId(),
            role: $user->getRoles(),
            firstname: $user->getFirstName(),
            lastname: $user->getLastName()
        );
    }
}
```

### Example 5: User Registration

```php
<?php

namespace App\Controllers;

use App\Kernel\AbstractController;
use App\Kernel\AbstractResponse;
use App\Kernel\Request;
use App\Kernel\Responses\JsonResponse;
use App\Security\User;
use App\Kernel\Security\CreateJwtAuth;

class RegisterController extends AbstractController
{
    public function register(): AbstractResponse
    {
        $request = Request::getRequestInstance();
        $data = $request->getAllData();
        $userRepository = new UserRepository();
        // Validate input
        if (empty($data['email']) || empty($data['password'])) {
            return new JsonResponse(['error' => 'Email and password required'], 400);
        }
        
        // Check if user exists
        if ($userRepository->findOneByEmail($data['email'])) {
            return new JsonResponse(['error' => 'User already exists'], 409);
        }
        
        // Create user
        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        $user->setFirstName($data['firstname'] ?? null);
        $user->setLastName($data['lastname'] ?? null);
        $user->setRoles(['user']);  // Default role
        $user->save();
        
        // Create authentication token
        $token = CreateJwtAuth::createToken(
            userId: $user->getId(),
            role: $user->getRoles(),
            firstname: $user->getFirstName(),
            lastname: $user->getLastName()
        );
        
        return new JsonResponse([
            'success' => true,
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getFirstName() . ' ' . $user->getLastName()
            ]
        ], 201);
    }
}
```

## Validity Periods

Common validity periods for different use cases:

```php
<?php

use App\Kernel\Security\CreateJwtAuth;

// 15 minutes - For sensitive operations
$token = CreateJwtAuth::createToken($userId, $roles, $firstName, $lastName, 900);

// 1 hour - For general API access
$token = CreateJwtAuth::createToken($userId, $roles, $firstName, $lastName, 3600);

// 24 hours - For web sessions (default)
$token = CreateJwtAuth::createToken($userId, $roles, $firstName, $lastName, 86400);

// 7 days - For "remember me" functionality
$token = CreateJwtAuth::createToken($userId, $roles, $firstName, $lastName, 604800);

// 30 days - For long-lived mobile app tokens
$token = CreateJwtAuth::createToken($userId, $roles, $firstName, $lastName, 2592000);
```

## Role Management

### Single Role

```php
<?php

use App\Kernel\Security\CreateJwtAuth;

$token = CreateJwtAuth::createToken(
    userId: 123,
    role: ['user'],
    firstname: 'John',
    lastname: 'Doe'
);
```

### Multiple Roles

```php
<?php

use App\Kernel\Security\CreateJwtAuth;

$token = CreateJwtAuth::createToken(
    userId: 456,
    role: ['admin', 'moderator', 'editor'],
    firstname: 'Jane',
    lastname: 'Smith'
);
```

### Dynamic Roles from User

```php
<?php

use App\Kernel\Security\CreateJwtAuth;
use App\Models\User;

$user = User::findById(789);

$token = CreateJwtAuth::createToken(
    userId: $user->getId(),
    role: $user->getRoles(),  // ['user', 'premium']
    firstname: $user->getFirstName(),
    lastname: $user->getLastName()
);
```

## Token Validation

After creating a token with `CreateJwtAuth`, validate it using `JwtToken`:

```php
<?php

use App\Kernel\Security\CreateJwtAuth;
use App\Kernel\Security\JwtToken;
use App\Kernel\GetEnvDatas;

// Create token
$token = CreateJwtAuth::createToken(123, ['user'], 'John', 'Doe');

// Later, validate the token
$jwt = new JwtToken();
$env = new GetEnvDatas();
$secret = $env->get('secret');

// 1. Check format
if (!JwtToken::checkFormat($token)) {
    die("Invalid token format");
}

// 2. Check signature
if (!$jwt->checkToken($token, $secret)) {
    die("Token is invalid or tampered");
}

// 3. Check expiration
if ($jwt->isExpired($token)) {
    die("Token has expired");
}

// 4. Get user data
$payload = $jwt->getPayload();
$userId = $payload['userId'];
$roles = $payload['role'];
$firstName = $payload['firstname'];
```

## Testing

The CreateJwtAuth class is tested through `CreateJwtAuthTest`:

### Test Coverage

```php
<?php

use App\Kernel\Security\CreateJwtAuth;
use App\Kernel\Security\JwtToken;
use PHPUnit\Framework\TestCase;

class CreateJwtAuthTest extends TestCase
{
    public function testCreateAuthToken(): void
    {
        // Create token with minimal parameters
        $token = CreateJwtAuth::createToken(
            userId: 1,
            role: ['admin'],
            firstname: 'John',
            lastname: 'Doe',
            validity: 86400
        );
        
        // Verify format
        $this->assertTrue(JwtToken::checkFormat($token));
        
        // Extract and verify payload
        $jwt = new JwtToken();
        $payload = $jwt->extractPayload($token);
        
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('userId', $payload);
        $this->assertEquals(1, $payload['userId']);
        $this->assertIsArray($payload['role']);
        $this->assertEquals('admin', $payload['role'][0]);
    }
}
```

## Environment Configuration

### Required Environment Variable

```bash
# .env file
secret=your-application-secret-key-here
```

The secret should be:
- At least 32 characters long
- A mix of uppercase, lowercase, numbers, and symbols
- Unique and securely generated
- Never committed to version control

### Example .env Setup

```bash
# Secret for JWT token signing
secret=kR9@mL#xY2pQ8$vW5nJ7&tU3fG1bH4sZ0aE6bC2lY4wP9pJ5mK3nL8qO1rS6tU

# Optional: Token validity period (in seconds)
token_validity=86400
```

## Best Practices

1. **Always use HTTPS** - Never transmit tokens over unencrypted connections
2. **Store tokens securely** - Use HttpOnly cookies or secure storage
3. **Validate on every request** - Always check tokens before processing
4. **Use appropriate validity** - Don't use excessively long validity periods
5. **Implement token refresh** - Use short tokens with refresh token mechanism
6. **Log authentication events** - Track all token creations and validations
7. **Protect the secret** - Store in environment variables, never hardcode
8. **Use strong secrets** - Generate cryptographically secure secrets
9. **Handle expiration gracefully** - Return clear error messages
10. **Implement logout** - Consider token blacklisting for explicit logouts

## Security Considerations

### Secret Key Security

```php
<?php

// ✓ GOOD: Load from environment
$secret = getenv('secret');

// ✓ GOOD: Use long, random secrets
// secret=kR9@mL#xY2pQ8$vW5nJ7&tU3fG1bH4sZ

// ✗ BAD: Hardcoded secrets
$secret = 'simple-password';

// ✗ BAD: Weak secrets
$secret = '123456';
```

### Token Storage

```php
<?php

// ✓ GOOD: Store in HttpOnly cookie (prevents XSS attacks)
setcookie('auth_token', $token, [
    'httponly' => true,
    'secure' => true,
    'samesite' => 'Strict'
]);

// ✓ GOOD: Store in Authorization header
$headers = ['Authorization' => 'Bearer ' . $token];

// ✗ BAD: Store in localStorage (vulnerable to XSS)
// localStorage.setItem('token', token);
```

### Token Validation Pattern

```php
<?php

use App\Kernel\Security\JwtToken;
use App\Kernel\GetEnvDatas;

class SecureTokenValidator
{
    public function validate(string $token): ?array
    {
        // 1. Check format
        if (!JwtToken::checkFormat($token)) {
            \App\Kernel\Logger::warning("Invalid token format attempt");
            return null;
        }

        // 2. Verify signature
        $jwt = new JwtToken();
        $env = new GetEnvDatas();
        $secret = $env->get('secret');

        if (!$jwt->checkToken($token, $secret)) {
            \App\Kernel\Logger::warning("Token tampering detected");
            return null;
        }

        // 3. Check expiration
        if ($jwt->isExpired($token)) {
            \App\Kernel\Logger::info("Expired token used");
            return null;
        }

        // 4. Validate payload structure
        $payload = $jwt->getPayload();
        if (!isset($payload['userId']) || !isset($payload['role'])) {
            \App\Kernel\Logger::error("Invalid token payload");
            return null;
        }

        // 5. Additional business logic checks
        if (!$this->isUserActive($payload['userId'])) {
            \App\Kernel\Logger::warning("Inactive user token used");
            return null;
        }

        return $payload;
    }

    private function isUserActive(int $userId): bool
    {
        // Check user exists and is active in database
        return true;
    }
}
```

## Complete Authentication Flow

### 1. User Registration

```php
<?php

// User submits registration form
$email = 'user@example.com';
$password = 'secure-password';
$firstName = 'John';
$lastName = 'Doe';

// Create user in database
$user = new User();
$user->setEmail($email);
$user->setPassword(password_hash($password, PASSWORD_BCRYPT));
$user->setFirstName($firstName);
$user->setLastName($lastName);
$user->setRoles(['user']);
$user->save();

// Create authentication token
$token = CreateJwtAuth::createToken(
    $user->getId(),
    $user->getRoles(),
    $user->getFirstName(),
    $user->getLastName()
);

// Return token to client
return ['token' => $token];
```

### 2. User Login

```php
<?php

// User submits login form
$email = 'user@example.com';
$password = 'secure-password';
$userRepository = new UserRepository();
// Find user in database
$user = $userRepository->findOneByEmail($email);

// Verify password
if (!$user || !password_verify($password, $user->getPassword())) {
    return ['error' => 'Invalid credentials'];
}

// Create authentication token
$token = CreateJwtAuth::createToken(
    $user->getId(),
    $user->getRoles(),
    $user->getFirstName(),
    $user->getLastName()
);

// Return token to client
return ['token' => $token];
```

### 3. Protected Request

```php
<?php

// Client sends request with token
$token = request.headers.get('Authorization').replace('Bearer ', '');

// Validate token
$jwt = new JwtToken();
$env = new GetEnvDatas();
$secret = $env->get('secret');

if (!JwtToken::checkFormat($token)) {
    return ['error' => 'Invalid token'];
}

if (!$jwt->checkToken($token, $secret)) {
    return ['error' => 'Unauthorized'];
}

if ($jwt->isExpired($token)) {
    return ['error' => 'Token expired'];
}

// Get user data from token
$payload = $jwt->getPayload();
$userId = $payload['userId'];
$roles = $payload['role'];

// Process request with user context
process_request($userId, $roles);
```

### 4. Token Refresh

```php
<?php

// User requests new token with expired one
$oldToken = request.headers.get('Authorization').replace('Bearer ', '');

// Extract user data from expired token
$jwt = new JwtToken();
$payload = $jwt->extractPayload($oldToken);  // Works even if expired

// Create new token with same user data
$newToken = CreateJwtAuth::createToken(
    $payload['userId'],
    $payload['role'],
    $payload['firstname'],
    $payload['lastname']
);

// Return new token
return ['token' => $newToken];
```

## Troubleshooting

### Issue: Missing Environment Variable

**Error**: Exception thrown when creating token

**Solution**: Ensure `secret` is set in .env file

```bash
# .env
secret=your-secret-key
```

### Issue: Token Validation Fails

**Cause**: Wrong secret key or token was tampered

**Solution**: Verify the same secret is used for validation

```php
<?php

// Use the same secret for both creation and validation
$env = new GetEnvDatas();
$secret = $env->get('secret');

// CreateJwtAuth::createToken() also uses this secret internally
$token = CreateJwtAuth::createToken($userId, $roles, $firstName, $lastName);
$jwt = new JwtToken();
$jwt->checkToken($token, $secret);  // Must use same secret
```

### Issue: Token Appears Expired

**Cause**: System time mismatch or validity period too short

**Solution**: Check server time and adjust validity period

```php
<?php

// Use longer validity period for debugging
$token = CreateJwtAuth::createToken(
    $userId,
    $roles,
    $firstName,
    $lastName,
    604800  // 7 days instead of 24 hours
);

// Check server time
echo date('Y-m-d H:i:s');
```

## API Client Examples

### JavaScript/Fetch

```javascript
// Login
const response = await fetch('/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email: 'user@example.com', password: 'password' })
});

const data = await response.json();
const token = data.token;

// Use token in requests
const apiResponse = await fetch('/api/protected', {
    headers: {
        'Authorization': `Bearer ${token}`
    }
});
```

### cURL

```bash
# Login
curl -X POST http://localhost/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# Get token from response
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."

# Protected request
curl -X GET http://localhost/api/protected \
  -H "Authorization: Bearer $TOKEN"
```

### Python Requests

```python
import requests

# Login
response = requests.post('http://localhost/login', json={
    'email': 'user@example.com',
    'password': 'password'
})

token = response.json()['token']

# Protected request
headers = {'Authorization': f'Bearer {token}'}
api_response = requests.get('http://localhost/api/protected', headers=headers)
```

## Summary

The `CreateJwtAuth` class provides a simplified, user-focused way to create authentication tokens. Use it whenever you need to:

- Create tokens after user login/registration
- Authenticate logged-in users
- Implement stateless authentication
- Build APIs with JWT tokens
- Create refresh token flows

Key features:
- Minimal parameters required
- Automatic secret key loading
- Pre-configured user payload
- Full JWT compliance
- Easy integration with authentication flows

Combine with `JwtToken` for comprehensive token management throughout your application.

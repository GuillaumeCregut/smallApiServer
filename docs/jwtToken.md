# JWT Token Documentation

## Overview

The `JwtToken` class is a secure implementation of JSON Web Tokens (JWT) using the HMAC SHA256 (HS256) algorithm. It provides methods to create, validate, and verify JWT tokens with built-in expiration support and payload extraction capabilities.

## Purpose

The JwtToken class serves to:
- **Create secure tokens** with HMAC SHA256 signatures
- **Validate token authenticity** through signature verification
- **Check token expiration** with timestamp validation
- **Extract payload data** from tokens securely
- **Verify token format** before processing
- **Store payload information** after validation

## Specifications

### JWT Standards Compliance

- **Algorithm**: HMAC SHA256 (HS256)
- **Standard**: RFC 7519 (JSON Web Token)
- **Encoding**: Base64URL encoding
- **Structure**: `header.payload.signature`

### Supported Claims

- **iat** (Issued At) - Timestamp when token was created
- **exp** (Expiration Time) - Timestamp when token expires
- **Custom claims** - Any additional data in the payload

## Class Structure

### Location

```
App\Kernel\Security\JwtToken
```

### Namespace

```php
namespace App\Kernel\Security;
```

### Properties

```php
private ?array $payload = null;  // Extracted payload
private bool $set = false;        // Flag indicating if payload is set
```

## Static Methods

### createToken()

Creates a new JWT token with the specified payload and secret.

```php
public static function createToken(
    array $payload,
    string $secret,
    int $validity = 86400
): string
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$payload` | array | Required | Data to encode in the token |
| `$secret` | string | Required | Secret key for HMAC signature |
| `$validity` | int | 86400 | Token validity in seconds (24 hours default) |

#### Return Value

Returns a JWT token string in format: `header.payload.signature`

#### Behavior

1. Creates a standard JWT header: `{"typ":"JWT","alg":"HS256"}`
2. Adds timestamp claims to payload:
   - `iat` - Current Unix timestamp
   - `exp` - Current timestamp + validity period
3. Encodes header and payload using Base64URL
4. Creates HMAC SHA256 signature
5. Combines all three parts with dots

#### Example

```php
<?php

use App\Kernel\Security\JwtToken;

$payload = [
    'userId' => 123,
    'username' => 'john_doe',
    'role' => 'admin'
];
$secret = 'your-secret-key-here';

// Create token with default 24-hour validity
$token = JwtToken::createToken($payload, $secret);

// Create token with 1-hour validity
$token = JwtToken::createToken($payload, $secret, 3600);


// Token format: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VySWQiOjEyMywidXNlcm5hbWUiOiJqb2huX2RvZSIsInJvbGUiOiJhZG1pbiIsImlhdCI6MTc2OTk1MzA4MywiZXhwIjoxNzcwMDM5NDgzfQ.GI5Sx4aNEH6GKneyo5cKzBwGX9mx-ndDO9sDghNbuSk
```

#### Validity Period

The validity parameter controls token expiration:

```php
// Default: 86400 seconds (24 hours)
$token = JwtToken::createToken($payload, $secret);

// 1 hour
$token = JwtToken::createToken($payload, $secret, 3600);

// 7 days
$token = JwtToken::createToken($payload, $secret, 604800);

```

## Instance Methods

### checkToken()

Validates a JWT token by verifying its signature and checking format.

```php
public function checkToken(string $token, string $secret): bool
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$token` | string | JWT token to validate |
| `$secret` | string | Secret key used to create the token |

#### Return Value

- **bool**: true if token is valid and signature matches, false otherwise

#### Behavior

1. Verifies token format (three parts separated by dots)
2. Extracts and decodes payload from token
3. Recreates the signature using the provided secret
4. Compares original signature with recreated signature
5. If valid, stores the payload internally
6. Returns validation result (doesn't check expiration)

#### Example

```php
<?php

use App\Kernel\Security\JwtToken;

$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...';
$secret = 'your-secret-key-here';

$jwt = new JwtToken();

// Check if token is valid
if ($jwt->checkToken($token, $secret)) {
    echo "Token is valid!";
    $payload = $jwt->getPayload();
    echo "User ID: " . $payload['userId'];
} else {
    echo "Token is invalid or tampered";
}
```

#### Important Notes

- This method does NOT check token expiration (use `isExpired()` for that)
- If the token is valid, the payload is stored and accessible via `getPayload()`
- If validation fails, no exception is thrown (returns false instead)
- Invalid tokens are silently rejected

### isExpired()

Checks if a JWT token has expired based on its `exp` claim.

```php
public function isExpired(string $token): bool
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$token` | string | JWT token to check |

#### Return Value

- **bool**: true if token is expired or invalid, false if token is valid and not expired

#### Behavior

1. Extracts payload from token
2. Reads the `exp` claim (expiration timestamp)
3. Compares with current Unix timestamp
4. Returns true if expiration time has passed
5. Returns true on any error (malformed token, missing exp claim)

#### Example

```php
<?php

use App\Kernel\Security\JwtToken;

$jwt = new JwtToken();
$token = JwtToken::createToken(['userId' => 1], 'secret', 3600);

// Check if token is expired
if ($jwt->isExpired($token)) {
    echo "Token has expired, please log in again";
} else {
    echo "Token is still valid";
}
```

#### Combined Validation

For full token validation, check both signature and expiration:

```php
<?php

$jwt = new JwtToken();
$token = 'your-jwt-token';
$secret = 'your-secret';

// Check format first
if (!JwtToken::checkFormat($token)) {
    die("Invalid token format");
}

// Then check signature
if (!$jwt->checkToken($token, $secret)) {
    die("Token signature is invalid");
}

// Finally check expiration
if ($jwt->isExpired($token)) {
    die("Token has expired");
}

// Token is valid and not expired
$payload = $jwt->getPayload();
```

### checkFormat()

Validates that a token has the correct JWT format (three base64-encoded parts separated by dots).

```php
public static function checkFormat(string $token): bool
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$token` | string | Token string to validate |

#### Return Value

- **bool**: true if token matches JWT format pattern, false otherwise

#### Token Format

Valid JWT tokens must match this pattern:
- Header: Base64URL encoded (alphanumeric, hyphens, underscores, equals)
- Dot separator
- Payload: Base64URL encoded
- Dot separator
- Signature: Base64URL encoded

Example: `xxxxx.yyyyy.zzzzz`

#### Example

```php
<?php

use App\Kernel\Security\JwtToken;

$validToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MX0.xxxxxxx';
$invalidToken = 'not-a-jwt-token';
$malformedToken = 'only.two.parts';

JwtToken::checkFormat($validToken);      // true
JwtToken::checkFormat($invalidToken);    // false
JwtToken::checkFormat($malformedToken);  // false
```

### extractPayload()

Safely extracts and decodes the payload from a JWT token.

```php
public function extractPayload(string $token): array
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$token` | string | JWT token to extract from |

#### Return Value

- **array**: Decoded payload data
- **throws InvalidArgumentException**: If token format is invalid
- **throws InvalidArgumentException**: If payload data is corrupted

#### Behavior

1. Validates token format
2. Splits token by dots
3. Extracts the middle section (payload)
4. Base64 decodes the payload
5. JSON decodes to array
6. Stores payload internally
7. Sets the internal flag `$set` to true
8. Returns the decoded payload

#### Example

```php
<?php

use App\Kernel\Security\JwtToken;

$token = JwtToken::createToken(['userId' => 123, 'role' => 'admin'], 'secret');

$jwt = new JwtToken();

try {
    $payload = $jwt->extractPayload($token);
    
    echo "User ID: " . $payload['userId'];      // 123
    echo "Role: " . $payload['role'];           // admin
    echo "Issued At: " . $payload['iat'];       // 1769953083
    echo "Expires At: " . $payload['exp'];      // 1770039483
    
} catch (InvalidArgumentException $e) {
    echo "Failed to extract payload: " . $e->getMessage();
}
```

#### Exception Cases

```php
<?php

// Malformed token (only 2 parts)
$jwt->extractPayload('header.payload');  // Throws InvalidArgumentException

// Invalid format
$jwt->extractPayload('not-a-token');     // Throws InvalidArgumentException

// Corrupted payload
$jwt->extractPayload('validheader.corrupted!!!.validsig');  // Throws InvalidArgumentException
```

### isSet()

Checks whether a payload has been loaded into the current token instance.

```php
public function isSet(): bool
```

#### Return Value

- **bool**: true if payload has been loaded (via `checkToken()` or `extractPayload()`), false otherwise

#### Example

```php
<?php

use App\Kernel\Security\JwtToken;

$jwt = new JwtToken();

echo $jwt->isSet();  // false - No payload loaded yet

$token = JwtToken::createToken(['userId' => 1], 'secret');
$jwt->checkToken($token, 'secret');

echo $jwt->isSet();  // true - Payload is now loaded
```

### getPayload()

Retrieves the stored payload data from the current token instance.

```php
public function getPayload(): ?array
```

#### Return Value

- **array**: The decoded payload if loaded
- **null**: If no payload has been loaded yet

#### Example

```php
<?php

use App\Kernel\Security\JwtToken;

$jwt = new JwtToken();

// Before any operation
$payload = $jwt->getPayload();  // null

// After checkToken
$token = JwtToken::createToken(['userId' => 123, 'role' => 'user'], 'secret');
$jwt->checkToken($token, 'secret');

$payload = $jwt->getPayload();
echo $payload['userId'];  // 123
echo $payload['role'];    // user

// After extractPayload
$payload = $jwt->extractPayload($token);
$payload = $jwt->getPayload();  // Same as above
```

## Complete Workflow Example

### Create, Validate, and Extract

```php
<?php

use App\Kernel\Security\JwtToken;

// Step 1: Create a token
$payload = [
    'userId' => 456,
    'username' => 'jane_smith',
    'email' => 'jane@example.com',
    'permissions' => ['read', 'write']
];

$secret = 'my-secret-key-do-not-share';
$token = JwtToken::createToken($payload, $secret, 3600);  // 1 hour validity

echo "Token created: " . $token . "\n";

// Step 2: Validate token later (e.g., in a different request)
$jwt = new JwtToken();

// Check format first
if (!JwtToken::checkFormat($token)) {
    die("Invalid token format");
}

// Check signature
if (!$jwt->checkToken($token, $secret)) {
    die("Token tampered or invalid");
}

// Check expiration
if ($jwt->isExpired($token)) {
    die("Token expired");
}

// Step 3: Extract and use payload
$storedPayload = $jwt->getPayload();

echo "User ID: " . $storedPayload['userId'] . "\n";
echo "Username: " . $storedPayload['username'] . "\n";
echo "Permissions: " . implode(', ', $storedPayload['permissions']) . "\n";
```

## Token Anatomy

### Header

```json
{
  "typ": "JWT",
  "alg": "HS256"
}
```

Base64URL encoded: `eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9`

### Payload

```json
{
  "userId": 123,
  "username": "john_doe",
  "role": "admin",
  "iat": 1769953083,
  "exp": 1770039483
}
```

Base64URL encoded: `eyJ1c2VySWQiOjEyMywidXNlcm5hbWUiOiJqb2huX2RvZSIsInJvbGUiOiJhZG1pbiIsImlhdCI6MTc2OTk1MzA4MywiZXhwIjoxNzcwMDM5NDgzfQ`

### Signature

HMAC SHA256 of `header.payload` using the secret:

```
GI5Sx4aNEH6GKneyo5cKzBwGX9mx-ndDO9sDghNbuSk
```

### Complete Token

```
eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VySWQiOjEyMywidXNlcm5hbWUiOiJqb2huX2RvZSIsInJvbGUiOiJhZG1pbiIsImlhdCI6MTc2OTk1MzA4MywiZXhwIjoxNzcwMDM5NDgzfQ.GI5Sx4aNEH6GKneyo5cKzBwGX9mx-ndDO9sDghNbuSk
```

## Use Cases

### 1. User Authentication

```php
<?php

use App\Kernel\Security\JwtToken;

class AuthService
{
    private string $secret = 'app-secret-key';

    public function generateLoginToken(array $user): string
    {
        $payload = [
            'userId' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        // 7 days validity
        return JwtToken::createToken($payload, $this->secret, 604800);
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

### 2. API Access Tokens

```php
<?php

use App\Kernel\Security\JwtToken;

class ApiTokenService
{
    private string $secret;

    public function createApiToken(int $clientId, array $scopes, int $hoursValidity = 24): string
    {
        $payload = [
            'clientId' => $clientId,
            'scopes' => $scopes,
            'type' => 'api'
        ];

        return JwtToken::createToken($payload, $this->secret, $hoursValidity * 3600);
    }

    public function validateApiToken(string $token): bool
    {
        $jwt = new JwtToken();

        if (!JwtToken::checkFormat($token)) {
            return false;
        }

        if (!$jwt->checkToken($token, $this->secret)) {
            return false;
        }

        if ($jwt->isExpired($token)) {
            return false;
        }

        // Additional checks
        $payload = $jwt->getPayload();
        return isset($payload['type']) && $payload['type'] === 'api';
    }
}
```

### 3. Email Verification Tokens

```php
<?php

use App\Kernel\Security\JwtToken;

class VerificationService
{
    private string $secret;

    public function createVerificationToken(int $userId, string $email): string
    {
        $payload = [
            'userId' => $userId,
            'email' => $email,
            'purpose' => 'email-verification'
        ];

        // 24 hours validity
        return JwtToken::createToken($payload, $this->secret, 86400);
    }

    public function verifyEmailToken(string $token, int $userId): bool
    {
        $jwt = new JwtToken();

        if (!JwtToken::checkFormat($token)) {
            return false;
        }

        if (!$jwt->checkToken($token, $this->secret)) {
            return false;
        }

        if ($jwt->isExpired($token)) {
            return false;
        }

        $payload = $jwt->getPayload();
        return $payload['userId'] === $userId && 
               $payload['purpose'] === 'email-verification';
    }
}
```

### 4. Password Reset Tokens

```php
<?php

use App\Kernel\Security\JwtToken;

class PasswordResetService
{
    private string $secret;

    public function generateResetToken(int $userId): string
    {
        $payload = [
            'userId' => $userId,
            'action' => 'password-reset'
        ];

        // 1 hour validity for security
        return JwtToken::createToken($payload, $this->secret, 3600);
    }

    public function validateResetToken(string $token, int $userId): bool
    {
        $jwt = new JwtToken();

        if (!JwtToken::checkFormat($token)) {
            return false;
        }

        if (!$jwt->checkToken($token, $this->secret)) {
            return false;
        }

        if ($jwt->isExpired($token)) {
            return false;
        }

        $payload = $jwt->getPayload();
        return $payload['userId'] === $userId && 
               $payload['action'] === 'password-reset';
    }
}
```

## Testing

The JwtToken class is thoroughly tested with the `JwtTokenTest` class:

### Test Coverage

| Test | Purpose |
|------|---------|
| `testCheckToken` | Verify token validation works |
| `testCreateToken` | Create and validate new tokens |
| `testStaticCreateToken` | Static method token creation |
| `testCheckFormatToken` | Format validation |
| `testTokenWithWrongFormat` | Reject malformed tokens |
| `testIsExpiredToken` | Expiration checking |
| `testWrongFormatToken` | Handle invalid tokens |
| `testExceptionExtractPayloadWithWrongToken` | Error handling |
| `testExtractPayload` | Payload extraction |
| `testGetPayload` | Payload retrieval |

### Example Tests

```php
<?php

use App\Kernel\Security\JwtToken;
use PHPUnit\Framework\TestCase;

class JwtTokenTest extends TestCase
{
    public function testCreateAndValidateToken(): void
    {
        $jwt = new JwtToken();
        $payload = ['userId' => 123];
        $secret = 'test-secret';

        // Create token
        $token = JwtToken::createToken($payload, $secret);

        // Validate it
        $this->assertTrue($jwt->checkToken($token, $secret));
    }

    public function testTokenExpiration(): void
    {
        $jwt = new JwtToken();
        $payload = ['userId' => 123];
        $secret = 'test-secret';

        // Create token with 1 second validity
        $token = JwtToken::createToken($payload, $secret, 1);

        // Should not be expired immediately
        $this->assertFalse($jwt->isExpired($token));

        // Wait for expiration
        sleep(2);

        // Should be expired now
        $this->assertTrue($jwt->isExpired($token));
    }

    public function testPayloadExtraction(): void
    {
        $jwt = new JwtToken();
        $payload = ['userId' => 456, 'role' => 'admin'];
        $secret = 'test-secret';

        $token = JwtToken::createToken($payload, $secret);
        $extracted = $jwt->extractPayload($token);

        $this->assertArrayHasKey('userId', $extracted);
        $this->assertEquals(456, $extracted['userId']);
        $this->assertEquals('admin', $extracted['role']);
    }
}
```

## Security Considerations

### Secret Key Management

```php
<?php

// ✓ GOOD: Store secret in environment variables
$secret = getenv('JWT_SECRET');

// ✓ GOOD: Use a strong, long random secret
// 32+ characters, mix of uppercase, lowercase, numbers, symbols
$secret = 'kR9@mL#xY2pQ8$vW5nJ7&tU3fG1bH4sZ';

// ✗ BAD: Hardcoded weak secrets
$secret = 'secret123';
```

### Token Validation Best Practices

```php
<?php

use App\Kernel\Security\JwtToken;

class SecureTokenValidator
{
    private string $secret;

    public function validateToken(string $token): ?array
    {
        // 1. Check format
        if (!JwtToken::checkFormat($token)) {
            // Log suspicious activity
            return null;
        }

        // 2. Check signature
        $jwt = new JwtToken();
        if (!$jwt->checkToken($token, $this->secret)) {
            // Log potential tampering
            return null;
        }

        // 3. Check expiration
        if ($jwt->isExpired($token)) {
            // Log expired token use
            return null;
        }

        // 4. Validate payload structure
        $payload = $jwt->getPayload();
        if (!isset($payload['userId'])) {
            // Log invalid payload
            return null;
        }

        // 5. Additional business logic validation
        if (!$this->isUserActive($payload['userId'])) {
            return null;
        }

        return $payload;
    }

    private function isUserActive(int $userId): bool
    {
        // Check if user still exists and is active
        return true;
    }
}
```

### Common Security Issues

| Issue | Solution |
|-------|----------|
| Weak secret | Use 32+ character random strings |
| Exposed secret | Store in environment variables |
| No expiration check | Always validate expiration |
| Token reuse after logout | Implement token blacklist |
| No signature verification | Always call `checkToken()` |
| Long validity periods | Use short-lived tokens (1-24 hours) |

## Encoding and Decoding

### Base64URL Encoding

The class uses Base64URL encoding (RFC 4648 Section 5):

```
Standard Base64: A-Za-z0-9+/=
Base64URL: A-Za-z0-9-_
```

Changes:
- `+` becomes `-`
- `/` becomes `_`
- `=` padding is removed

```php
<?php

// Standard Base64
base64_encode('test') // "dGVzdA=="

// Base64URL (used by JWT)
str_replace(['+', '/', '='], ['-', '_', ''], base64_encode('test')) // "dGVzdA"
```

## Troubleshooting

### Issue: "Invalid token format"

**Cause**: Token doesn't match JWT format pattern

**Solution**: Ensure token has three parts separated by dots

```php
<?php

$token = 'invalid-token';
JwtToken::checkFormat($token);  // false

// Valid token
$token = 'header.payload.signature';
JwtToken::checkFormat($token);  // true
```

### Issue: Token validation fails but format is correct

**Cause**: Secret key doesn't match or token was tampered

**Solution**: Verify you're using the same secret that created the token

```php
<?php

$jwt = new JwtToken();
$token = JwtToken::createToken(['id' => 1], 'secret1');

$jwt->checkToken($token, 'secret1');  // true
$jwt->checkToken($token, 'secret2');  // false - Wrong secret!
```

### Issue: Token appears valid but payload is null

**Cause**: Payload hasn't been loaded yet

**Solution**: Call `checkToken()` or `extractPayload()` first

```php
<?php

$jwt = new JwtToken();
$jwt->getPayload();  // null - Not loaded yet

$token = JwtToken::createToken(['id' => 1], 'secret');
$jwt->checkToken($token, 'secret');
$jwt->getPayload();  // Now returns the payload
```

## Best Practices

1. **Always validate in this order**: Format → Signature → Expiration
2. **Use HTTPS** - Always transmit tokens over encrypted connections
3. **Store securely** - Never expose the secret key
4. **Short validity** - Use 1-24 hour tokens, not days/weeks
5. **Implement refresh tokens** - For long-term sessions, use separate refresh tokens
6. **Validate payload** - Check that required claims exist and have expected values
7. **Log security events** - Log all validation failures
8. **Rotate secrets** - Change secrets periodically
9. **Use strong secrets** - At least 32 random characters
10. **Implement token blacklisting** - For logout functionality

## Summary

The JwtToken class provides a secure, standards-compliant JWT implementation with:
- HS256 (HMAC SHA256) signing
- RFC 7519 compliance
- Expiration support
- Payload extraction
- Format validation
- Clean, easy-to-use API

Use it for:
- User authentication
- API access tokens
- Email verification links
- Password reset tokens
- Session management
- Stateless authentication

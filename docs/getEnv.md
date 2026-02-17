# GetEnvDatas - Environment Configuration Management

## Overview

`GetEnvDatas` is a singleton utility class that manages environment configuration variables loaded from an INI file. It provides a centralized way to access application configuration settings such as database credentials, API keys, and other environment-specific parameters.

## Features

- **Singleton Pattern**: Ensures only one instance of environment configuration exists throughout the application lifecycle
- **INI File Support**: Loads configuration from standard PHP INI files
- **Type-Safe Access**: Methods return mixed types or arrays with default value support
- **Database Credentials Helper**: Quick access to common database parameters
- **Error Handling**: Throws `KernelException` for configuration loading errors
- **Instance Control**: Ability to reset singleton instance for testing purposes

## Class Location

```
Kernel/GetEnvDatas.php
```

## Namespace

```php
App\Kernel
```

## Dependencies

- `App\Kernel\Exceptions\KernelException` - Custom exception class for kernel errors

## Installation & Setup

### Basic Usage

Initialize the environment configuration in your application bootstrap (typically in `Kernel.php`):

```php
use App\Kernel\GetEnvDatas;
use App\Kernel\Exceptions\KernelException;

try {
    $iniFile = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . '.env';
    $env = GetEnvDatas::getEnvInstance($iniFile);
} catch (KernelException $e) {
    // Handle configuration error
    die('Configuration error: ' . $e->getMessage());
}
```

## Methods

### `__construct(string $iniFile)`

Creates a new GetEnvDatas instance by parsing an INI file.

**Parameters:**
- `$iniFile` (string): Path to the INI configuration file

**Throws:**
- `KernelException` - If the INI file cannot be parsed or is invalid

**Example:**
```php
$env = new GetEnvDatas('/path/to/.env');
```

---

### `get(string $key, $default = null): mixed`

Retrieves a single environment variable value by key.

**Parameters:**
- `$key` (string): The configuration key to retrieve
- `$default` (mixed, optional): Default value if key is not found (default: `null`)

**Returns:**
- `mixed` - The configuration value or default if not found

**Example:**
```php
$env = GetEnvDatas::getEnvInstance();

$apiKey = $env->get('api_key');
$timeout = $env->get('request_timeout', 30); // Returns 30 if not set
$debug = $env->get('DEBUG_MODE', false);
```

---

### `getAll(): array`

Returns all environment variables as an associative array.

**Returns:**
- `array` - All loaded environment variables as key-value pairs

**Example:**
```php
$env = GetEnvDatas::getEnvInstance();
$allConfigs = $env->getAll();

// Result: ['host' => 'localhost', 'db' => 'myapp', ...]
```

---

### `getDdCredentials(): array`

Retrieves database credentials as a structured array.

**Returns:**
- `array` - Associative array with keys: `host`, `db`, `user`, `pass`

**Expected INI Keys:**
- `host` - Database host address
- `db` - Database name
- `user` - Database username
- `pass` - Database password

**Example:**
```php
$env = GetEnvDatas::getEnvInstance();
$dbCreds = $env->getDdCredentials();

// Result:
// [
//     'host' => 'localhost',
//     'db' => 'myapp_db',
//     'user' => 'dbuser',
//     'pass' => 'dbpass123'
// ]
```

---

### `static getEnvInstance(?string $envFile = null): GetEnvDatas`

Retrieves or creates the singleton instance of GetEnvDatas.

**Parameters:**
- `$envFile` (string|null, optional): Path to the INI file. Required on first call, optional on subsequent calls.

**Returns:**
- `GetEnvDatas` - The singleton instance

**Throws:**
- `KernelException` - If:
  - `$envFile` is not provided and instance is not initialized
  - `$envFile` path does not exist
  - INI file is malformed or cannot be parsed

**Example:**
```php
// First call - must provide path
$env = GetEnvDatas::getEnvInstance('/path/to/.env');

// Subsequent calls - can use without argument
$env = GetEnvDatas::getEnvInstance();
$env2 = GetEnvDatas::getEnvInstance(); // Same instance as $env
```

---

### `static getAppPath(): string`

Returns the application root directory path.

**Returns:**
- `string` - Absolute path to the application root with trailing directory separator

**Example:**
```php
$appPath = GetEnvDatas::getAppPath();
// Result: /var/www/html/myapp/ or C:\Users\Dev\Projects\myapp\
```

---

### `static resetInstance(): void`

Resets the singleton instance. Useful for testing or re-initialization.

**Example:**
```php
GetEnvDatas::resetInstance();
$env = GetEnvDatas::getEnvInstance('/new/path/.env');
```

## Configuration File Format

The INI file should follow standard PHP INI syntax:

```ini
# Database Configuration
DB_HOST=localhost
DB_NAME=myapp_database
DB_USER=db_user
DB_PASS=db_password
DB_PORT=3306

# Application Settings
JWT_SECRET=your_secret
api_key=your_api_key
DEBUG_MODE=true
request_timeout=30
```

**Notes:**
- Line comments start with `#`
- Key-value pairs use `=` separator
- No spacing around `=` is generally recommended
- PHP `parse_ini_file()` is used, so follow its conventions

## Usage in Kernel

Here's how `GetEnvDatas` is used in the main application kernel:

```php
class Kernel
{
    public function __construct()
    {
        // Define path to environment file
        $iniFile = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . '.env';
        
        try {
            // Initialize environment singleton
            $env = GetEnvDatas::getEnvInstance($iniFile);
        } catch (KernelException $e) {
            // Handle configuration error gracefully
            $response = new ErrorResponse(500);
            return $response;
        }
        
        // Rest of kernel initialization...
        $this->routes = Router::getRoutes();
        $datas = GetClientParams::getInputs();
        // ...
    }
}
```

## Testing

### Test Suite

The class is tested in `tests/unit/kernel/EnvDatas/GetEnvDatasTest.php`

### Key Test Cases

1. **testGetAppPath()** - Validates application path calculation
2. **testLoadEnvErrorFile()** - Validates exception when no file is provided
3. **testLoadEnvMisformedFile()** - Validates exception for invalid INI file
4. **testLoadFalseEnvFile()** - Validates exception for non-existent file
5. **testLoadEnvFileValues()** - Validates successful loading and value retrieval
6. **testLoadDBCredentials()** - Validates database credentials extraction

### Example Test

```php
use App\Kernel\GetEnvDatas;
use PHPUnit\Framework\TestCase;

class GetEnvDatasTest extends TestCase
{
    public function testLoadEnvFileValues(): void
    {
        // Reset singleton for clean test
        GetEnvDatas::resetInstance();
        
        $filename = __DIR__ . DIRECTORY_SEPARATOR . '.env.sample';
        $env = GetEnvDatas::getEnvInstance($filename);
        
        // Verify loaded value
        $this->assertEquals('your_secret', $env->get('JWT_SECRET'));
        
        // Verify singleton works - same instance returned
        $env2 = GetEnvDatas::getEnvInstance();
        $this->assertEquals('your_secret', $env2->get('JWT_SECRET'));
    }
}
```

## Error Handling

### Common Exceptions

| Error | Cause | Solution |
|-------|-------|----------|
| `Env not initialized correctly` | `getEnvInstance()` called without file on first call | Provide `$envFile` parameter on first call |
| `Env file not found : {path}` | Specified INI file doesn't exist | Verify file path and permissions |
| `Error loading env File` | INI file is malformed or unreadable | Check INI file syntax and format |

### Error Handling Pattern

```php
try {
    $env = GetEnvDatas::getEnvInstance($envFile);
    $secret = $env->get('JWT_SECRET');
} catch (KernelException $e) {
    error_log('Config error: ' . $e->getMessage());
    // Fallback to defaults or fail gracefully
    $secret = 'default_secret_value';
}
```

## Best Practices

1. **Initialize Early**: Call `getEnvInstance()` in application bootstrap (usually `Kernel.php`)
2. **Use Defaults**: Always provide default values for optional configuration:
   ```php
   $timeout = $env->get('timeout', 30);
   ```
3. **Never Commit .env**: Add `.env` to `.gitignore`, use `.env.sample` for examples
4. **Secure Sensitive Data**: Don't log or expose credentials
5. **Reset in Tests**: Use `resetInstance()` before each test to ensure isolation
6. **Validate Configuration**: Check required keys exist during initialization

## Example: Complete Usage

```php
<?php

namespace App;

use App\Kernel\Kernel;
use App\Kernel\GetEnvDatas;

// 1. Load configuration
$env = GetEnvDatas::getEnvInstance(__DIR__ . '/.env');

// 2. Access individual values
$debug = $env->get('DEBUG_MODE', false);
$apiKey = $env->get('api_key');

// 3. Get all configuration
$allConfig = $env->getAll();

// 4. Access database credentials
$dbCreds = $env->getDdCredentials();

// 5. Initialize application kernel
$kernel = new Kernel();
$response = $kernel->route();

// 6. Return response
echo $response;
```

## Performance Considerations

- **Singleton Pattern**: Configuration is loaded only once, subsequent calls return the cached instance
- **Late Initialization**: Pass `$envFile` only on first call; subsequent calls reuse the instance
- **Memory Efficient**: Entire configuration stored in a single array

## Related Classes

- `Kernel` - [Uses GetEnvDatas for configuration initialization](./kernel.md)
- `DatabaseConnector` - Uses database credentials from GetEnvDatas
- `KernelException` - Exception thrown by GetEnvDatas
- `Router` - [May access environment for routing configuration](./router.md)

## Changelog

### Version 1.0 (Current)
- Initial implementation of GetEnvDatas singleton
- Support for INI file parsing
- Database credentials helper method
- Full unit test coverage

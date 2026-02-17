# Logger - Application Logging System

## Overview

The `Logger` class provides a centralized, static logging system for the SmallApiServer framework. It captures application events at three severity levels (error, warning, info) with configurable output to system files or custom log files.

**Location**: `Kernel\Logger.php`

**Namespace**: `App\Kernel`

**Type**: Static utility class (no instantiation required)

**Key Features**:
- Three log levels: ERROR, WARNING, INFO
- Flexible output: System PHP logs or custom files
- Debug-only mode for development logging
- Automatic file and directory creation
- Configurable log paths via environment variables
- Automatic timestamp and caller information

## Architecture

### Logging Flow

```
Logger::error/warning/info()
    ↓
Get environment configuration (paths, debug mode)
    ↓
Check if should log (debug mode filter)
    ↓
Format message with timestamp and caller
    ↓
Output to system file OR custom file
    ↓
Create directories/files if needed
```

### Configuration

Logger configuration comes from environment variables via `GetEnvDatas`:

| Variable | Purpose | Example | Default |
|----------|---------|---------|---------|
| `ERROR_LOG_PATH` | Path to error log file | `logs/error` | Empty (system log) |
| `WARNING_LOG_PATH` | Path to warning log file | `logs/warning` | Empty (system log) |
| `INFO_LOG_PATH` | Path to info log file | `logs/info` | Empty (system log) |
| `DEBUG_MODE` | Enable/disable debug logging | `true` or `false` | false |

**Example .env**:
```env
DEBUG_MODE=true
ERROR_LOG_PATH=logs/error
WARNING_LOG_PATH=logs/warning
INFO_LOG_PATH=logs/info
```

## Logging Methods

All logging methods are `public static` and follow the same signature pattern.

### error()

**Purpose**: Log error-level messages

**Signature**:
```php
public static function error(
    object|string $sender,
    string $message,
    bool $debugOnly,
    bool $systemFile
): void
```

**Parameters**:
- `$sender` (object|string): The object or class name that triggered the error
  - If object: Automatically extracts class name
  - If string: Used directly as sender identifier
- `$message` (string): Error message to log
- `$debugOnly` (bool): If true, only log when debug mode is enabled
- `$systemFile` (bool): If true, use PHP's error_log instead of custom file

**Example**:
```php
// From a controller
Logger::error($this, 'User not found with ID: 42', false, false);

// From a static context
Logger::error(UserRepository::class, 'Database connection failed', false, true);

// Debug-only logging
Logger::error($this, 'Detailed user data: ' . print_r($user, true), true, false);
```

**Output Format**:
```
ERROR: 2026-02-17 14:35:22 : App\Controllers\UserController : User not found with ID: 42
```

**Use Cases**:
- Database connection failures
- File upload errors
- Invalid user input
- Exception handling
- Authorization failures

### warning()

**Purpose**: Log warning-level messages

**Signature**:
```php
public static function warning(
    object $sender,
    string $message,
    bool $debugOnly,
    bool $systemFile
): void
```

**Parameters**: Same as `error()`, except `$sender` must be an object (no string variant)

**Example**:
```php
// Deprecated method usage
Logger::warning($this, 'Using deprecated API method', false, false);

// Slow database query
Logger::warning($this, 'Query executed in 5.2 seconds', true, false);

// Missing optional configuration
Logger::warning($this, 'Cache configuration not found, using defaults', false, false);
```

**Output Format**:
```
WARNING: 2026-02-17 14:35:22 : App\Services\UserService : Slow query detected
```

**Use Cases**:
- Deprecated function usage
- Performance warnings
- Missing optional configuration
- Unusual but recoverable conditions
- Data validation warnings

### info()

**Purpose**: Log informational messages

**Signature**:
```php
public static function info(
    object $sender,
    string $message,
    bool $debugOnly,
    bool $systemFile
): void
```

**Parameters**: Same as `warning()` (object required)

**Example**:
```php
// User login
Logger::info($this, 'User 123 logged in successfully', false, false);

// Data import
Logger::info($this, 'Imported 500 users from CSV file', false, false);

// Development info
Logger::info($this, 'Cache cleared', true, false);
```

**Output Format**:
```
INFO: 2026-02-17 14:35:22 : App\Controllers\AuthController : User login successful
```

**Use Cases**:
- User login/logout
- Data import/export completion
- System initialization events
- Configuration loading
- Scheduled task execution

## Private Helper Methods

These methods support the public logging methods.

### getEnvValues()

**Purpose**: Retrieve logging configuration from environment

**Signature**:
```php
private static function getEnvValues(string $path): array
```

**Parameters**:
- `$path` (string): Environment variable name (e.g., 'ERROR_LOG_PATH')

**Returns**: Array with keys:
- `debugMode` (bool): Whether debug mode is enabled
- `path` (string): Log file path from environment
- `appPath` (string): Application root path

**Logic**:
- Reads debug mode with `FILTER_VALIDATE_BOOLEAN`
- Reads custom log path from environment
- Combines with app root path to create full path

### createMessage()

**Purpose**: Format log message with timestamp and sender

**Signature**:
```php
private static function createMessage(
    string $sender,
    string $message,
    string $level
): string
```

**Parameters**:
- `$sender` (string): Class/object name
- `$message` (string): Log message
- `$level` (string): Log level (ERROR, WARNING, INFO)

**Features**:
- Strips null characters from message (security)
- Sets timezone to 'Europe/Paris'
- Formats timestamp as 'Y-m-d H:i:s'
- Returns formatted string

**Example Output**:
```
ERROR: 2026-02-17 14:35:22 : App\Kernel\Database : Connection timeout
```

### checkLogFile()

**Purpose**: Verify log file exists and is writable

**Signature**:
```php
private static function checkLogFile(string $filePath): bool
```

**Parameters**:
- `$filePath` (string): Full path to log file

**Returns**: `true` if file exists and is writable, `false` otherwise

**Process**:
1. If file doesn't exist, calls `createPath()` to create it
2. Checks if file is writable
3. Returns status

### createPath()

**Purpose**: Create log file and directory structure

**Signature**:
```php
private static function createPath(string $filename): bool
```

**Parameters**:
- `$filename` (string): Full path including filename

**Returns**: `true` if successful, `false` otherwise

**Process**:
1. Extracts directory from filename
2. Creates directory recursively if missing (permissions: 0766)
3. Creates empty log file with `touch()`
4. Logs errors via system error_log if creation fails
5. Returns success status

**Directory Permissions**: `0755` (owner read/write/execute, others read/execute)

### getCallerName()

**Purpose**: Extract class name from sender

**Signature**:
```php
private static function getCallerName(object|string $caller): string
```

**Parameters**:
- `$caller` (object|string): Sender identifier

**Returns**: Class name as string

**Logic**:
- If object: Returns `get_class($caller)`
- If string: Returns `(string)$caller`

## Log Output Targets

### System File (PHP error_log)

When `$systemFile = true`:
- Uses PHP's built-in `error_log()` function
- Writes to PHP's configured error log
- Typically in `php.ini` error_log location
- No file path logging configuration needed

**Use Cases**:
- Critical system errors
- When custom logging fails
- Server administration logging
- Quick debugging

### Custom Files

When `$systemFile = false`:
- Uses environment variable for log path
- Creates custom log file in `logs/` directory
- Automatic directory creation
- Automatic newline handling

**File Structure**:
```
logs/
├── error           # Error log file
├── warning         # Warning log file
└── info            # Info log file
```

## Log Format

All log messages follow this format:

```
[LEVEL]: [YYYY-MM-DD HH:MM:SS] : [ClassName] : [Message]
```

**Example**:
```
ERROR: 2026-02-17 14:35:22 : App\Controllers\UserController : Database connection failed
WARNING: 2026-02-17 14:35:23 : App\Services\EmailService : Slow SMTP response
INFO: 2026-02-17 14:35:24 : App\Middleware\AuthMiddleware : User 42 authenticated
```

## Debug-Only Logging

The `$debugOnly` parameter controls whether logs are created based on environment configuration:

```php
// Always logged (logs if custom file configured)
Logger::error($this, 'Critical error', false, false);

// Only logged when DEBUG_MODE=true
Logger::error($this, 'Debug information', true, false);
```

**Benefits**:
- Reduces log file size in production
- Development debugging without production impact
- Easy filtering of development logs

## Best Practices

### 1. Use Appropriate Log Levels

```php
// ✓ Good: Correct severity level
Logger::error($this, 'Database query failed', false, false);
Logger::warning($this, 'Slow database query', true, false);
Logger::info($this, 'User logged in', false, false);

// ✗ Avoid: Wrong severity levels
Logger::error($this, 'User logged in', false, false);     // Should be info()
Logger::warning($this, 'Database crashed', false, false); // Should be error()
```

### 2. Provide Context in Messages

```php
// ✓ Good: Specific context
Logger::error($this, 'SQL Error #1044: Access denied for user: john', false, false);
Logger::error($this, 'File upload failed (5MB limit exceeded): photo.jpg', false, false);

// ✗ Avoid: Vague messages
Logger::error($this, 'Something went wrong', false, false);
Logger::error($this, 'Error', false, false);
```

### 3. Don't Log Sensitive Data

```php
// ✓ Good: No sensitive data
Logger::error($this, 'Failed login attempt for user ID: 42', false, false);

// ✗ Avoid: Logging passwords, tokens
Logger::error($this, 'Login failed: user=alice, password=xyz123', false, false);
Logger::error($this, 'Invalid API token: sk-12345abcde', false, false);
```

### 4. Configure Log Paths for Different Environments

```env
# Production
ERROR_LOG_PATH=/var/log/app/error
WARNING_LOG_PATH=/var/log/app/warning
DEBUG_MODE=false

# Development
ERROR_LOG_PATH=logs/error
WARNING_LOG_PATH=logs/warning
INFO_LOG_PATH=logs/info
DEBUG_MODE=true
```

### 5. Use Debug-Only for Verbose Logging

```php
// ✓ Good: Development debugging only
Logger::info($this, 'Query executed: ' . $query, true, false);
Logger::info($this, 'Full response: ' . json_encode($data), true, false);

// ✓ Good: Always log important events
Logger::info($this, 'User login successful', false, false);
```

### 6. Handle Logging Failures Gracefully

```php
// ✓ Good: Don't let logging break the app
try {
    Logger::error($this, 'Processing error', false, false);
} catch (Exception $e) {
    // Silently fail - logging shouldn't crash app
    error_log('Logging failed: ' . $e->getMessage());
}
```

### 7. Log at Decision Points

```php
// ✓ Good: Log before important decisions
if (!$user) {
    Logger::error($this, 'User lookup failed for ID: ' . $userId, false, false);
    return $this->returnError(404);
}

Logger::info($this, 'Processing payment for user: ' . $user->id, false, false);
$result = $this->processPayment($user);

if (!$result) {
    Logger::error($this, 'Payment processing failed', false, false);
}
```

### 8. Use Object When Possible

```php
// ✓ Good: Automatic class extraction
Logger::error($this, 'Error message', false, false);

// Acceptable: String class name
Logger::error(self::class, 'Error message', false, false);

// Works: Direct string
Logger::error('UserService', 'Error message', false, false);
```

## Integration with Framework

### In Controllers

```php
class UserController extends AbstractController
{
    public function create()
    {
        try {
            $data = $this->request->get();
            Logger::info($this, 'User creation attempt', false, false);
            
            $user = $this->userRepository->create($data);
            Logger::info($this, 'User created: ' . $user->id, false, false);
            
            $this->response->setBody($user);
        } catch (Exception $e) {
            Logger::error($this, 'User creation failed: ' . $e->getMessage(), false, false);
            $this->returnError(500);
        }
    }
}
```

### In Services

```php
class EmailService
{
    public function send($to, $subject, $body)
    {
        Logger::info($this, 'Sending email to: ' . $to, false, false);
        
        try {
            $result = $this->mailer->send($to, $subject, $body);
            Logger::info($this, 'Email sent successfully', false, false);
            return $result;
        } catch (Exception $e) {
            Logger::error($this, 'Email sending failed: ' . $e->getMessage(), true, true);
            throw $e;
        }
    }
}
```

### In Repositories

```php
class UserRepository extends AbstractRepository
{
    public function findActive()
    {
        Logger::info($this, 'Fetching active users', true, false); // Debug only
        
        try {
            $results = $this->query()
                ->where('status', '=', 'active')
                ->fetch();
                
            Logger::info($this, 'Found ' . count($results) . ' active users', true, false);
            return $results;
        } catch (Exception $e) {
            Logger::error($this, 'Failed to fetch active users: ' . $e->getMessage(), false, false);
            throw $e;
        }
    }
}
```

### Exception Handling

```php
try {
    $user = $this->userRepository->find($id);
    if (!$user) {
        Logger::warning($this, 'User not found: ' . $id, false, false);
        throw new UserNotFoundException();
    }
} catch (UserNotFoundException $e) {
    Logger::error($this, 'User lookup failed: ' . $e->getMessage(), false, false);
    return $this->returnError(404);
} catch (Exception $e) {
    Logger::error($this, 'Unexpected error: ' . $e->getMessage(), false, false);
    return $this->returnError(500);
}
```

## Complete Example

```php
<?php
// UserRepository.php
namespace App\Repository;

use App\Kernel\Logger;

class UserRepository
{
    private $connector;
    
    public function __construct($connector)
    {
        $this->connector = $connector;
        Logger::info($this, 'UserRepository initialized', true, false);
    }
    
    public function create($data)
    {
        Logger::info($this, 'Creating new user: ' . $data['email'], false, false);
        
        try {
            // Validate
            if (!$this->validateEmail($data['email'])) {
                Logger::warning($this, 'Invalid email format: ' . $data['email'], false, false);
                throw new InvalidEmailException();
            }
            
            // Check duplicate
            if ($this->findByEmail($data['email'])) {
                Logger::warning($this, 'Email already exists: ' . $data['email'], false, false);
                throw new DuplicateEmailException();
            }
            
            // Insert
            $id = $this->connector->insert('users', $data);
            Logger::info($this, 'User created successfully: ID ' . $id, false, false);
            
            return $this->find($id);
            
        } catch (Exception $e) {
            Logger::error($this, 'User creation failed: ' . $e->getMessage(), false, true);
            throw $e;
        }
    }
    
    public function delete($id)
    {
        Logger::info($this, 'Deleting user: ' . $id, false, false);
        
        try {
            $result = $this->connector->query()
                ->delete('users')
                ->where('id', '=', $id)
                ->execute();
            
            Logger::info($this, 'User deleted: ' . $id, false, false);
            return $result;
            
        } catch (Exception $e) {
            Logger::error($this, 'User deletion failed: ' . $e->getMessage(), false, true);
            throw $e;
        }
    }
    
    public function find($id)
    {
        Logger::info($this, 'Looking up user: ' . $id, true, false); // Debug only
        
        try {
            $result = $this->connector->query()
                ->select('*')
                ->from('users')
                ->where('id', '=', $id)
                ->fetch();
            
            if (!$result) {
                Logger::warning($this, 'User not found: ' . $id, false, false);
                return null;
            }
            
            return $result[0] ?? null;
            
        } catch (Exception $e) {
            Logger::error($this, 'User lookup error: ' . $e->getMessage(), false, true);
            throw $e;
        }
    }
}
```

## Troubleshooting

| Problem | Cause | Solution |
|---------|-------|----------|
| Logs not appearing | Log paths not configured in .env | Set ERROR_LOG_PATH, WARNING_LOG_PATH, or INFO_LOG_PATH |
| `Permission denied` on log file | Directory permissions incorrect | Check that logs/ directory has write permissions (755) |
| Logs in PHP error_log only | `$systemFile = true` used | Pass `false` for `$systemFile` parameter to use custom files |
| Debug logs not appearing | DEBUG_MODE not enabled | Set DEBUG_MODE=true in .env for debug-only messages |
| "Directory can't be created" message | Invalid path in .env | Ensure path is valid and server has write permissions in parent directory |
| Null characters in logs | Special characters in message | Logger automatically strips null characters, may indicate encoding issue |
| Very large log files | Too much verbose logging | Enable `$debugOnly = true` for development logs in production |
| Logs disappear on restart | Using PHP error_log | Configure persistent logging path in .env instead of system file |

## Environment Configuration

### Full .env Example

```env
# Enable/disable debug mode
DEBUG_MODE=true

# Log file locations (relative to app root)
ERROR_LOG_PATH=logs/error
WARNING_LOG_PATH=logs/warning
INFO_LOG_PATH=logs/info

# Production .env
DEBUG_MODE=false
ERROR_LOG_PATH=/var/log/app/error.log
WARNING_LOG_PATH=/var/log/app/warning.log
# INFO_LOG_PATH not needed in production
```

### Without Custom Paths (System Logging)

```env
# Only system PHP error_log will be used
DEBUG_MODE=false
# Don't set ERROR_LOG_PATH, WARNING_LOG_PATH, INFO_LOG_PATH
```

## Performance Considerations

### Log File Size Management

```php
// ✓ Good: Reasonable message size
Logger::info($this, 'User ID ' . $userId . ' logged in', false, false);

// ✗ Avoid: Huge messages that inflate log files
Logger::info($this, 'Large data: ' . print_r($largeArray, true), false, false);
```

### Debug Mode in Production

```php
// ✓ Good: Disable verbose logging in production
if ($debugMode) {
    Logger::info($this, 'Detailed query: ' . $sql, true, false);
}

// ✗ Avoid: Verbose logging in production
Logger::info($this, 'Detailed query: ' . $sql, false, false);
```

### Batch Operations

```php
// ✓ Good: Log summary, not each item
$count = 0;
foreach ($users as $user) {
    $count += $this->save($user) ? 1 : 0;
}
Logger::info($this, 'Saved ' . $count . ' users', false, false);

// ✗ Avoid: Logging every iteration
foreach ($users as $user) {
    Logger::info($this, 'Saving: ' . $user->name, false, false);
}
```

## See Also

- [Tools & Utilities](tools.md) - Dumper and debugging utilities
- [Kernel](kernel.md) - Application lifecycle and error handling
- [Console System](console.md) - CLI logging capabilities
- [GetEnv](getEnv.md) - Environment configuration
- [Exception Handling](AbstractController.md) - Controller exception handling

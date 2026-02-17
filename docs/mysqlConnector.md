# MySQL Connector Documentation

## Overview

The `MySQLConnector` is the database adapter that manages connections to MySQL databases. It implements the `ConnectorInterface` and uses PHP's PDO (PHP Data Objects) extension for secure database operations with parameter binding and prepared statements.

**Key Features:**
- Singleton pattern for single database connection
- Automatic connection retry with exponential backoff
- Prepared statements for SQL injection prevention
- Transaction support (BEGIN, COMMIT, ROLLBACK)
- Consistent error handling via `DatabaseException`
- UTF-8 character encoding by default

---

## Location & Namespace

```php
namespace App\Kernel\Connector;

class MySQLConnector implements ConnectorInterface
```

**File**: `Kernel/Connector/MysqlConnector.php`

---

## Singleton Pattern

The `MySQLConnector` uses the Singleton pattern to ensure only one database connection exists per application lifecycle.

### Getting the Instance

```php
use App\Kernel\Connector\MySQLConnector;

// First initialization with credentials
$credentials = [
    'host' => 'localhost',
    'db' => 'myapp',
    'user' => 'root',
    'pass' => 'password',
    'port' => 3306  // Optional, defaults to 3306
];

$connector = MySQLConnector::getInstance($credentials);

// Subsequent calls return the same instance
$connector = MySQLConnector::getInstance();
```

### Resetting the Instance

For testing or reconnection:

```php
MySQLConnector::resetInstance();
```

### Required Credentials

The `$credentials` array must contain:

| Key | Type | Required | Default | Description |
|-----|------|----------|---------|-------------|
| `host` | string | ✅ Yes | - | MySQL server hostname or IP |
| `db` | string | ✅ Yes | - | Database name |
| `user` | string | ✅ Yes | - | Database username |
| `pass` | string | ✅ Yes | - | Database password |
| `port` | int | ❌ No | 3306 | MySQL server port |

---

## Connection Management

### Direct Connection Access

```php
$pdo = $connector->getConnection();
// Returns: PDO object for advanced operations
```

### Connection Retry Mechanism

The connector automatically retries failed connections with exponential backoff:

- **Max Retries**: 3 attempts
- **Base Delay**: 1000ms (1 second)
- **Delay Formula**: `1000 * 2^(attempt-1)` ms
  - Attempt 1: 1000ms
  - Attempt 2: 2000ms
  - Attempt 3: 4000ms

**Stop Codes** (immediate failure, no retry):

| Code | Error |
|------|-------|
| 1044 | Access denied for user to database |
| 1045 | Access denied (incorrect password) |
| 1049 | Unknown database |
| 1130 | Host not allowed to connect |

---

## Query Methods

### executeQuery() - INSERT, UPDATE, DELETE

Executes modification queries (INSERT, UPDATE, DELETE).

```php
public function executeQuery(string $sql, array $params = []): int|bool
```

**Parameters:**
- `$sql` (string): SQL query with `:placeholder` syntax
- `$params` (array, optional): Parameter values

**Return Values:**
- For **INSERT**: Returns the last inserted ID (integer)
- For **UPDATE/DELETE**: Returns `true` if rows affected, `false` if no rows affected
- Throws `DatabaseException` on error

**Examples:**

```php
// INSERT - Returns new ID
$id = $connector->executeQuery(
    "INSERT INTO users (name, email) VALUES (:name, :email)",
    ['name' => 'John', 'email' => 'john@example.com']
);
echo "New user ID: $id";

// UPDATE - Returns boolean
$success = $connector->executeQuery(
    "UPDATE users SET name = :name WHERE id = :id",
    ['name' => 'Jane', 'id' => 1]
);

// DELETE - Returns boolean
$success = $connector->executeQuery(
    "DELETE FROM users WHERE id = :id",
    ['id' => 1]
);
```

### fetchQuery() - SELECT (Multiple Rows)

Retrieves multiple rows from a SELECT query.

```php
public function fetchQuery(string $sql, array $params = []): array
```

**Parameters:**
- `$sql` (string): SELECT query with `:placeholder` syntax
- `$params` (array, optional): Parameter values

**Return Value:**
- Returns array of associative arrays (rows)
- Empty array if no results
- Throws `DatabaseException` on error

**Examples:**

```php
// Get all users
$users = $connector->fetchQuery("SELECT * FROM users");
// Returns: [
//   ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
//   ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com']
// ]

// Get users with conditions
$users = $connector->fetchQuery(
    "SELECT * FROM users WHERE role = :role ORDER BY name",
    ['role' => 'admin']
);

// Get specific columns
$names = $connector->fetchQuery(
    "SELECT id, name FROM users WHERE active = :active",
    ['active' => 1]
);
```

### fetchQueryOnce() - SELECT (Single Row)

Retrieves a single row from a SELECT query.

```php
public function fetchQueryOnce(string $sql, array $params = []): ?array
```

**Parameters:**
- `$sql` (string): SELECT query with `:placeholder` syntax
- `$params` (array, optional): Parameter values

**Return Value:**
- Returns associative array (single row) or `null` if not found
- Throws `DatabaseException` on error

**Examples:**

```php
// Get user by ID
$user = $connector->fetchQueryOnce(
    "SELECT * FROM users WHERE id = :id",
    ['id' => 1]
);

if ($user) {
    echo "User: " . $user['name'];
} else {
    echo "User not found";
}

// Get user by email
$user = $connector->fetchQueryOnce(
    "SELECT * FROM users WHERE email = :email",
    ['email' => 'john@example.com']
);
```

---

## Transactions

### Start Transaction

```php
$connector->startTransac();
```

Begins a database transaction. All queries after this are not committed until `commitTransac()` is called.

### Commit Transaction

```php
$connector->commitTransac();
```

Commits all changes made since `startTransac()`.

### Rollback Transaction

```php
$connector->rollBack();
```

Reverts all changes made since `startTransac()`.

### Transaction Example

```php
try {
    $connector->startTransac();
    
    // Insert parent record
    $parentId = $connector->executeQuery(
        "INSERT INTO orders (customer_id, total) VALUES (:customer_id, :total)",
        ['customer_id' => 1, 'total' => 99.99]
    );
    
    // Insert child records
    $connector->executeQuery(
        "INSERT INTO order_items (order_id, product_id, quantity) VALUES (:order_id, :product_id, :quantity)",
        ['order_id' => $parentId, 'product_id' => 5, 'quantity' => 2]
    );
    
    // All queries succeeded, commit changes
    $connector->commitTransac();
    echo "Order created successfully";
    
} catch (DatabaseException $e) {
    // Something went wrong, rollback all changes
    $connector->rollBack();
    echo "Order creation failed: " . $e->getMessage();
}
```

---

## Error Handling

All database operations throw `DatabaseException` on error.

### DatabaseException

```php
use App\Kernel\Connector\DatabaseException;

try {
    $result = $connector->fetchQuery($sql, $params);
} catch (DatabaseException $e) {
    echo "Error: " . $e->getMessage();
    echo "Code: " . $e->getCode();
}
```

### Common Error Codes

| Code | Meaning | Solution |
|------|---------|----------|
| 1045 | Access denied | Check username/password |
| 1049 | Unknown database | Verify database name exists |
| 2002 | Can't connect to server | Check host/port, server is running |
| 1130 | Host not allowed | Check user host privileges |
| 1054 | Unknown column | Check column name in query |
| 1064 | SQL syntax error | Review your SQL query |

### Connection Failure Example

```php
use App\Kernel\Exceptions\KernelException;
use App\Kernel\Connector\DatabaseException;

try {
    $env = [
        'host' => 'localhost',
        'db' => 'myapp',
        'user' => 'invalid_user',
        'pass' => 'wrong_password'
    ];
    $connector = MySQLConnector::getInstance($env);
} catch (DatabaseException $e) {
    // Access denied or connection failed
    echo "Database connection failed: " . $e->getMessage();
} catch (KernelException $e) {
    // Missing required credentials
    echo "Configuration error: " . $e->getMessage();
}
```

---

## Security Features

### Prepared Statements

All queries use prepared statements to prevent SQL injection:

```php
// ✓ SAFE - Uses parameter binding
$connector->executeQuery(
    "SELECT * FROM users WHERE email = :email",
    ['email' => $userEmail]
);

// ✗ UNSAFE - String concatenation (never do this!)
$connector->executeQuery("SELECT * FROM users WHERE email = '$userEmail'");
```

### Character Encoding

UTF-8 configuration is applied automatically:

```php
// Connector sets this automatically
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false,
// Charset: utf8mb4 (supports all Unicode characters including emojis)
```

### No Password Logging

Credentials are stored privately and never logged:

```php
private array $credentials = [];  // Protected from access
```

---

## Configuration

### From Environment Variables

Typically used with `GetEnvDatas`:

```php
use App\Kernel\GetEnvDatas;

$env = GetEnvDatas::getEnvInstance();
$dbCreds = $env->getDdCredentials(); // Returns credentials array

$connector = MySQLConnector::getInstance($dbCreds);
```

### From .env File

```ini
host=localhost
port=3306
db=myapp_db
user=app_user
pass=secure_password
```

### Kernel Integration

The kernel automatically initializes the connector:

```php
// In Kernel.php
$connector = DatabaseConnector::getConnector();
ConnectorDispatcher::setConnector($connector);
```

---

## Usage in Repositories

The connector is primarily used through the Repository pattern:

```php
use App\Kernel\Connector\AbstractRepository;

class UserRepository extends AbstractRepository
{
    protected ?string $entity = User::class;
    
    // AbstractRepository uses $this->connector internally
    public function findActiveUsers(): array
    {
        return $this->findBy(['active' => 1]);
    }
}

// Usage
$repo = new UserRepository();
$users = $repo->findAll();  // Uses MySQLConnector internally
```

---

## Testing

### Test Configuration

Tests require valid database credentials:

```php
protected function setUp(): void
{
    MySQLConnector::resetInstance();
    $this->validEnv = [
        'host' => 'localhost',
        'db' => 'test_database',
        'user' => 'test_user',
        'pass' => 'test_password'
    ];
}

protected function tearDown(): void
{
    MySQLConnector::resetInstance();
}
```

### Test Examples

```php
use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\MySQLConnector;
use App\Kernel\Exceptions\KernelException;

class MysqlConnectorTest extends TestCase
{
    // Test initialization without credentials throws exception
    public function testGetInstanceThrowsExceptionWhenNotInitialized(): void
    {
        $this->expectException(KernelException::class);
        MySQLConnector::getInstance();
    }

    // Test missing credentials throws exception
    public function testGetInstanceThrowsExceptionWhenBadEnv(): void
    {
        $env = ['host' => 'localhost'];  // Missing required keys
        $this->expectException(KernelException::class);
        MySQLConnector::getInstance($env);
    }

    // Test invalid credentials throws exception
    public function testGetInstanceThrowsExceptionWhenFalseEnv(): void
    {
        $env = [
            'host' => 'localhost',
            'db' => 'test',
            'user' => 'invalid',
            'pass' => 'wrong'
        ];
        $this->expectException(DatabaseException::class);
        MySQLConnector::getInstance($env);
    }

    // Test singleton behavior
    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = MySQLConnector::getInstance($env);
        $instance2 = MySQLConnector::getInstance();
        $this->assertSame($instance1, $instance2);
    }
}
```

---

## Best Practices

### 1. Always Use Parameter Binding

```php
// ✓ GOOD - Safe from SQL injection
$connector->executeQuery(
    "INSERT INTO users (name, email) VALUES (:name, :email)",
    ['name' => $name, 'email' => $email]
);

// ✗ BAD - Vulnerable to SQL injection
$connector->executeQuery(
    "INSERT INTO users (name, email) VALUES ('$name', '$email')"
);
```

### 2. Use Transactions for Related Operations

```php
// ✓ GOOD - Atomic operation
try {
    $connector->startTransac();
    $parentId = $connector->executeQuery("INSERT INTO parents ...", $data1);
    $connector->executeQuery("INSERT INTO children ...", [$parentId]);
    $connector->commitTransac();
} catch (Exception $e) {
    $connector->rollBack();
}

// ✗ BAD - Queries might partially succeed
$connector->executeQuery("INSERT INTO parents ...", $data1);
$connector->executeQuery("INSERT INTO children ...", $data2);
```

### 3. Use Repositories Instead of Direct Connector

```php
// ✓ GOOD - Use Repository pattern
$repo = new UserRepository();
$users = $repo->findAll();

// ✗ BAD - Direct connector usage in controller
$sql = "SELECT * FROM users";
$users = $connector->fetchQuery($sql);
```

### 4. Handle Exceptions Appropriately

```php
// ✓ GOOD - Handle specific exceptions
try {
    $result = $connector->fetchQuery($sql, $params);
} catch (DatabaseException $e) {
    // Log error
    error_log("Database error: " . $e->getMessage());
    // Return user-friendly error
    return $this->returnError(500);
}

// ✗ BAD - Ignore exceptions
$result = @$connector->fetchQuery($sql, $params);
```

### 5. Validate Parameters Before Query

```php
// ✓ GOOD - Validate input
$id = (int)$request->getData('id');
if ($id <= 0) {
    return $this->returnError(422);
}
$user = $connector->fetchQueryOnce(
    "SELECT * FROM users WHERE id = :id",
    ['id' => $id]
);

// ✗ BAD - No validation
$user = $connector->fetchQueryOnce(
    "SELECT * FROM users WHERE id = :id",
    ['id' => $request->getData('id')]
);
```

### 6. Close Transactions Properly

```php
// ✓ GOOD - Always cleanup
try {
    $connector->startTransac();
    // ... operations
    $connector->commitTransac();
} catch (Exception $e) {
    $connector->rollBack();
    throw $e;
}

// ✗ BAD - Might leave transaction open
$connector->startTransac();
// ... operations (exception might not commit)
$connector->commitTransac();
```

---

## Integration with ConnectorDispatcher

The connector is accessed through the dispatcher singleton:

```php
use App\Kernel\Connector\ConnectorDispatcher;

// Get the connector
$connector = ConnectorDispatcher::getConnector();

// Execute query
$result = $connector->fetchQuery($sql, $params);
```

---

## Performance Considerations

### Connection Pooling

For high-traffic applications, consider using connection pooling at the system level:

```ini
# MySQL configuration
max_connections = 1000
max_user_connections = 500
```

### Query Optimization

```php
// ✓ GOOD - Specific columns
$users = $connector->fetchQuery("SELECT id, name, email FROM users");

// ✗ BAD - Unnecessary data transfer
$users = $connector->fetchQuery("SELECT * FROM users");
```

### Batch Operations

```php
// ✓ GOOD - Use transactions for batch operations
$connector->startTransac();
foreach ($data as $item) {
    $connector->executeQuery($sql, $item);
}
$connector->commitTransac();

// ✗ BAD - Individual transactions (slower)
foreach ($data as $item) {
    $connector->startTransac();
    $connector->executeQuery($sql, $item);
    $connector->commitTransac();
}
```

---

## Troubleshooting

### Connection Issues

| Error | Cause | Solution |
|-------|-------|----------|
| "Host is not allowed to connect" | User permissions | Grant privileges: `GRANT ALL ON db.* TO 'user'@'host'` |
| "Can't connect to server" | Server down or wrong host | Check server is running, verify hostname/IP |
| "Access denied" | Wrong credentials | Verify username/password in .env |
| "Unknown database" | Database doesn't exist | Create database first |

### Query Issues

| Error | Cause | Solution |
|-------|-------|----------|
| "Unknown column" | Column name wrong | Check column name in schema |
| "Syntax error" | Invalid SQL | Review query syntax |
| "No affected rows" | WHERE clause matches nothing | Verify data exists |

---

## Complete Example

```php
<?php

use App\Kernel\Connector\MySQLConnector;
use App\Kernel\Connector\DatabaseException;

// Initialize connector
$credentials = [
    'host' => 'localhost',
    'db' => 'shop_db',
    'user' => 'shop_user',
    'pass' => 'shop_password'
];

$connector = MySQLConnector::getInstance($credentials);

try {
    // Start transaction for order creation
    $connector->startTransac();
    
    // Create order
    $orderId = $connector->executeQuery(
        "INSERT INTO orders (customer_id, order_date, total) 
         VALUES (:customer_id, :order_date, :total)",
        [
            'customer_id' => 5,
            'order_date' => date('Y-m-d H:i:s'),
            'total' => 149.99
        ]
    );
    
    // Add order items
    $connector->executeQuery(
        "INSERT INTO order_items (order_id, product_id, quantity, price) 
         VALUES (:order_id, :product_id, :quantity, :price)",
        [
            'order_id' => $orderId,
            'product_id' => 10,
            'quantity' => 2,
            'price' => 49.99
        ]
    );
    
    // Commit transaction
    $connector->commitTransac();
    
    // Fetch order details
    $order = $connector->fetchQueryOnce(
        "SELECT * FROM orders WHERE id = :id",
        ['id' => $orderId]
    );
    
    echo "Order created: " . json_encode($order);
    
} catch (DatabaseException $e) {
    $connector->rollBack();
    echo "Error: " . $e->getMessage();
}
```

---

## Summary

| Feature | Description |
|---------|-------------|
| **Pattern** | Singleton for single connection |
| **Interface** | Implements `ConnectorInterface` |
| **Queries** | `executeQuery()`, `fetchQuery()`, `fetchQueryOnce()` |
| **Transactions** | `startTransac()`, `commitTransac()`, `rollBack()` |
| **Security** | Prepared statements, parameter binding |
| **Encoding** | UTF-8 (utf8mb4) by default |
| **Retry** | 3 attempts with exponential backoff |
| **Errors** | Throws `DatabaseException` on failure |

---

**Last Updated**: February 17, 2026

For more information, see [Repository Documentation](./repository.md)


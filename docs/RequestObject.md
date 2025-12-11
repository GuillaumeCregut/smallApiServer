# RequestObject Documentation

## Overview

`RequestObject` is a singleton class that encapsulates all HTTP request data and provides a unified interface for accessing request information. It handles data extraction from various sources (GET, POST, JSON body, headers, files, sessions) and normalizes them into a consistent format. This class acts as the central point for accessing request data throughout the application.

**Location:** `Kernel/RequestObject.php`  
**Namespace:** `App\Kernel`  
**Pattern:** Singleton

## Class Definition

```php
class RequestObject
```

## Properties

### Private Properties

#### `$instance` (static, ?RequestObject)
- **Type:** `static ?RequestObject`
- **Description:** Singleton instance of RequestObject
- **Default:** `null`
- **Scope:** Private static
- **Purpose:** Ensures only one RequestObject instance exists throughout the application lifecycle

#### `$method` (string)
- **Type:** `string`
- **Description:** HTTP request method (GET, POST, PUT, PATCH, DELETE, etc.)
- **Scope:** Private
- **Initialized from:** `$_SERVER['REQUEST_METHOD']`

#### `$datas` (array)
- **Type:** `array`
- **Description:** Merged request data from multiple sources (GET, POST, JSON body)
- **Scope:** Private
- **Initialized from:** `getDatas()` method
- **Contains:** Query parameters, form data, and JSON body data

#### `$headers` (array)
- **Type:** `array`
- **Description:** All HTTP request headers
- **Scope:** Private
- **Initialized from:** `getallheaders()` PHP function

#### `$files` (array)
- **Type:** `array`
- **Description:** Processed file uploads converted to FileUpload objects
- **Scope:** Private
- **Initialized from:** `convertFiles()` method
- **Format:** `['field_name' => [FileUpload, FileUpload, ...]]`

#### `$server` (array)
- **Type:** `array`
- **Description:** Server and execution environment information
- **Scope:** Private
- **Initialized from:** `$_SERVER` superglobal

#### `$sessions` (array)
- **Type:** `array`
- **Description:** Session data
- **Scope:** Private
- **Initialized from:** `$_SESSION` superglobal

## Methods

### Constructor

```php
public function __construct()
```

**Description:**  
Initializes the RequestObject by extracting and normalizing all request data from various PHP superglobals.

**Parameters:** None

**Return Type:** void

**Behavior:**
- Extracts HTTP method from `$_SERVER['REQUEST_METHOD']`
- Merges data from GET, POST, and JSON body
- Retrieves all HTTP headers
- Stores server information
- Stores session data
- Converts file uploads to FileUpload objects

**Note:** This constructor is called automatically when using the singleton pattern via `getRequestInstance()`.

---

### Private Methods

#### `decodeJSON(string $json): array`

```php
private function decodeJSON(string $json): array
```

**Description:**  
Safely decodes JSON strings into PHP arrays with error handling.

**Parameters:**
- `$json` (string) - JSON string to decode

**Return Type:** `array`

**Return Values:**
- **Success:** Associative array of decoded JSON data
- **Failure:** Empty array if JSON is invalid

**Behavior:**
- Uses `json_decode()` with `true` flag for associative arrays
- Checks for JSON errors using `json_last_error()`
- Returns empty array on decode failure (prevents exceptions)

**Example:**
```php
$json = '{"name": "John", "age": 30}';
$data = $this->decodeJSON($json);
// Result: ['name' => 'John', 'age' => 30]
```

---

#### `convertFiles(): array`

```php
private function convertFiles(): array
```

**Description:**  
Converts raw `$_FILES` superglobal data into FileUpload objects for easier handling.

**Parameters:** None

**Return Type:** `array`

**Return Values:**
- Associative array where keys are form field names and values are arrays of FileUpload objects

**Behavior:**
1. Uses `FileFormator::convert()` to normalize `$_FILES` structure
2. Iterates through each file field
3. Creates FileUpload objects for each file
4. Organizes files by field name in a container array

**Example:**
```php
// Input: $_FILES['avatar'] = [file data]
// Output: ['avatar' => [FileUpload object]]

// Input: $_FILES['documents'] = [multiple files]
// Output: ['documents' => [FileUpload, FileUpload, FileUpload]]
```

---

#### `getDatas(): array`

```php
private function getDatas(): array
```

**Description:**  
Extracts and merges request data from multiple sources based on HTTP method.

**Parameters:** None

**Return Type:** `array`

**Return Values:**
- Merged associative array of all request data

**Behavior by HTTP Method:**

| Method | Data Sources | Merge Order |
|--------|--------------|-------------|
| GET | JSON body + `$_GET` | JSON first, then GET |
| POST | `$_POST` + JSON body + `$_GET` | POST, JSON, GET |
| PUT | JSON body + `$_GET` | JSON, GET |
| PATCH | JSON body + `$_GET` | JSON, GET |
| DELETE | JSON body + `$_GET` | JSON, GET |

**Data Extraction:**
- Reads raw JSON from `php://input` stream
- Decodes JSON safely using `decodeJSON()`
- Merges with superglobal data using `array_merge()`

**Example:**
```php
// GET request with query string and JSON body
// URL: /api/users?page=1
// Body: {"name": "John"}
// Result: ['page' => '1', 'name' => 'John']
```

---

#### `makeRoute(string $route): string`

```php
private function makeRoute(string $route): string
```

**Description:**  
Processes and sanitizes the URI route, extracting numeric IDs from the path and adding them to request data.

**Parameters:**
- `$route` (string) - Raw URI path

**Return Type:** `string`

**Return Values:**
- Sanitized route path without trailing numeric ID

**Behavior:**
1. Sanitizes the route using `FILTER_SANITIZE_URL`
2. Splits route by `/` delimiter
3. Checks if the last segment is numeric (ID)
4. If numeric and not already in data, adds it as `id` parameter
5. Removes the ID from the route path
6. Returns the cleaned route

**Example:**
```php
// Input: '/api/users/123'
// Output: '/api/users'
// Side effect: Sets $this->datas['id'] = 123

// Input: '/api/users/john'
// Output: '/api/users/john'
// Side effect: No ID extraction
```

---

### Public Methods

#### `getMethod(): string`

```php
public function getMethod(): string
```

**Description:**  
Returns the HTTP request method.

**Parameters:** None

**Return Type:** `string`

**Return Values:**
- HTTP method: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS`, etc.

**Example:**
```php
$method = $request->getMethod();
if ($method === 'POST') {
    // Handle POST request
}
```

---

#### `getAllDatas(): array`

```php
public function getAllDatas(): array
```

**Description:**  
Returns all merged request data from GET, POST, and JSON body.

**Parameters:** None

**Return Type:** `array`

**Return Values:**
- Associative array containing all request parameters

**Example:**
```php
$data = $request->getAllDatas();
// ['id' => 1, 'name' => 'John', 'email' => 'john@example.com']
```

---

#### `isAuth(): bool`

```php
public function isAuth(): bool
```

**Description:**  
Checks if the request is authenticated.

**Parameters:** None

**Return Type:** `bool`

**Return Values:**
- `true` if authenticated
- `false` if not authenticated

**Current Implementation:**
- Always returns `false` (marked as TODO for implementation)

**Note:** This method is intended for future authentication implementation.

---

#### `getAuthUser(): ?array`

```php
public function getAuthUser(): ?array
```

**Description:**  
Extracts and parses the Authorization header.

**Parameters:** None

**Return Type:** `?array`

**Return Values:**
- **Success:** Array with two elements: `[type, credentials]`
  - `[0]` - Authorization type (e.g., "Bearer", "Basic")
  - `[1]` - Authorization credentials (token or encoded credentials)
- **Failure:** `null` if Authorization header is not present

**Behavior:**
1. Retrieves the `Authorization` header
2. Returns `null` if header doesn't exist
3. Splits header by space (max 2 parts)
4. Returns array with type and credentials

**Example:**
```php
// Request header: Authorization: Bearer eyJhbGciOiJIUzI1NiIs...
$auth = $request->getAuthUser();
// Result: ['Bearer', 'eyJhbGciOiJIUzI1NiIs...']

// Request without Authorization header
$auth = $request->getAuthUser();
// Result: null
```

---

#### `setData(string $key, mixed $value): void`

```php
public function setData(string $key, mixed $value): void
```

**Description:**  
Adds or updates a data value in the request data array.

**Parameters:**
- `$key` (string) - Data key
- `$value` (mixed) - Data value (any type)

**Return Type:** void

**Behavior:**
- Sets or overwrites the value at the specified key
- Allows adding computed or extracted data to the request

**Example:**
```php
$request->setData('user_id', 42);
$request->setData('is_admin', true);
$request->setData('metadata', ['role' => 'admin']);
```

---

#### `getFiles(): array`

```php
public function getFiles(): array
```

**Description:**  
Returns all uploaded files as FileUpload objects.

**Parameters:** None

**Return Type:** `array`

**Return Values:**
- Associative array: `['field_name' => [FileUpload, FileUpload, ...]]`

**Example:**
```php
$files = $request->getFiles();
// ['avatar' => [FileUpload object], 'documents' => [FileUpload, FileUpload]]
```

---

#### `getFile(string $key): ?array`

```php
public function getFile(string $key): ?array
```

**Description:**  
Returns uploaded files for a specific form field.

**Parameters:**
- `$key` (string) - Form field name

**Return Type:** `?array`

**Return Values:**
- **Success:** Array of FileUpload objects for the field
- **Failure:** `null` if field doesn't exist

**Example:**
```php
$avatarFiles = $request->getFile('avatar');
// Result: [FileUpload object] or null

if ($avatarFiles) {
    foreach ($avatarFiles as $file) {
        // Process file
    }
}
```

---

#### `getRequestInstance(): RequestObject` (static)

```php
public static function getRequestInstance(): RequestObject
```

**Description:**  
Returns the singleton instance of RequestObject, creating it if necessary.

**Parameters:** None

**Return Type:** `RequestObject`

**Return Values:**
- The singleton RequestObject instance

**Behavior:**
- Checks if instance is null
- Creates new instance if needed
- Returns existing instance on subsequent calls

**Example:**
```php
$request = RequestObject::getRequestInstance();
// First call: Creates new instance
$request2 = RequestObject::getRequestInstance();
// Second call: Returns same instance
// $request === $request2 (true)
```

---

#### `getURI(): string`

```php
public function getURI(): string
```

**Description:**  
Returns the sanitized URI route for routing purposes.

**Parameters:** None

**Return Type:** `string`

**Return Values:**
- Cleaned route path without leading/trailing slashes or numeric IDs

**Behavior:**
1. Extracts path from `REQUEST_URI` using `parse_url()`
2. Trims leading/trailing slashes
3. Processes route through `makeRoute()` to extract IDs
4. Returns the final route

**Example:**
```php
// Request URI: /api/users/123?page=1
$uri = $request->getURI();
// Result: 'api/users'
// Side effect: Sets $this->datas['id'] = 123

// Request URI: /
$uri = $request->getURI();
// Result: ''
```

---

#### `getSessionValue(string $name): mixed`

```php
public function getSessionValue(string $name): mixed
```

**Description:**  
Retrieves a value from the session.

**Parameters:**
- `$name` (string) - Session key name

**Return Type:** `mixed`

**Return Values:**
- Session value if key exists
- `null` if key doesn't exist

**Example:**
```php
$userId = $request->getSessionValue('user_id');
$theme = $request->getSessionValue('theme');
```

---

#### `setSessionValue(string $name, mixed $value): void`

```php
public function setSessionValue(string $name, mixed $value): void
```

**Description:**  
Sets a value in the session.

**Parameters:**
- `$name` (string) - Session key name
- `$value` (mixed) - Value to store (any type)

**Return Type:** void

**Behavior:**
- Updates both the internal `$sessions` array and `$_SESSION` superglobal
- Ensures session persistence

**Example:**
```php
$request->setSessionValue('user_id', 42);
$request->setSessionValue('theme', 'dark');
$request->setSessionValue('preferences', ['language' => 'en']);
```

---

## Singleton Pattern

RequestObject implements the Singleton pattern to ensure only one instance exists throughout the application lifecycle.

### Usage

```php
// Get the singleton instance
$request = RequestObject::getRequestInstance();

// All subsequent calls return the same instance
$request2 = RequestObject::getRequestInstance();
assert($request === $request2); // true
```

### Benefits

- Single source of truth for request data
- Consistent data access across the application
- Memory efficient (only one instance)
- Thread-safe initialization

---

## Data Flow

### Request Processing Flow

```
HTTP Request
    ↓
Constructor called
    ├─ Extract HTTP method
    ├─ Merge data from GET, POST, JSON
    ├─ Extract headers
    ├─ Store server info
    ├─ Store session data
    └─ Convert files to FileUpload objects
    ↓
RequestObject instance ready
    ↓
Access via getRequestInstance()
```

### Data Merging Priority

For POST requests:
```
$_POST (highest priority)
    ↓
JSON body
    ↓
$_GET (lowest priority)
```

Later values override earlier ones in `array_merge()`.

---

## Dependencies

### Internal Dependencies

- **FileFormator** (`App\Kernel\Files\FileFormator`)
  - Used to normalize `$_FILES` structure

- **FileUpload** (`App\Kernel\Files\FileUpload`)
  - Wraps individual file uploads

### PHP Superglobals Used

- `$_SERVER` - Server and execution environment
- `$_GET` - Query string parameters
- `$_POST` - Form data
- `$_FILES` - File uploads
- `$_SESSION` - Session data

### PHP Functions Used

- `getallheaders()` - Retrieve HTTP headers
- `json_decode()` - Parse JSON
- `json_last_error()` - Check JSON errors
- `file_get_contents()` - Read request body
- `parse_url()` - Parse URI
- `filter_var()` - Sanitize URL
- `explode()` - Split strings
- `array_merge()` - Combine arrays

---

## Usage Examples

### Basic Request Handling

```php
$request = RequestObject::getRequestInstance();

// Get HTTP method
$method = $request->getMethod();

// Get all data
$data = $request->getAllDatas();

// Get specific data
$userId = $data['id'] ?? null;
$name = $data['name'] ?? null;
```

### Handling Different HTTP Methods

```php
$request = RequestObject::getRequestInstance();

switch ($request->getMethod()) {
    case 'GET':
        $id = $request->getAllDatas()['id'] ?? null;
        // Fetch data
        break;
    case 'POST':
        $data = $request->getAllDatas();
        // Create new record
        break;
    case 'PUT':
        $data = $request->getAllDatas();
        // Update record
        break;
    case 'DELETE':
        $id = $request->getAllDatas()['id'] ?? null;
        // Delete record
        break;
}
```

### Working with Files

```php
$request = RequestObject::getRequestInstance();

// Get all files
$allFiles = $request->getFiles();

// Get files from specific field
$avatarFiles = $request->getFile('avatar');

if ($avatarFiles) {
    foreach ($avatarFiles as $file) {
        // $file is a FileUpload object
        // Process file
    }
}
```

### Authentication

```php
$request = RequestObject::getRequestInstance();

$auth = $request->getAuthUser();

if ($auth) {
    $type = $auth[0];        // 'Bearer'
    $token = $auth[1];       // JWT token
    
    if ($type === 'Bearer') {
        // Validate JWT token
    }
}
```

### Session Management

```php
$request = RequestObject::getRequestInstance();

// Get session value
$userId = $request->getSessionValue('user_id');

// Set session value
$request->setSessionValue('user_id', 42);
$request->setSessionValue('last_activity', time());
```

### Adding Custom Data

```php
$request = RequestObject::getRequestInstance();

// Extract ID from URI and add to data
$request->setData('id', 123);

// Add computed values
$request->setData('is_admin', true);

// Retrieve all data including custom values
$allData = $request->getAllDatas();
```

---

## Error Handling

### JSON Decode Errors

If JSON body is invalid:
- Returns empty array instead of throwing exception
- Allows graceful fallback to other data sources

```php
// Invalid JSON in body
// Result: Empty array, falls back to GET/POST data
```

### Missing Authorization Header

If Authorization header is missing:
- Returns `null` instead of throwing exception
- Allows checking for authentication without errors

```php
$auth = $request->getAuthUser();
if ($auth === null) {
    // No authorization header
}
```

### Missing Files

If file field doesn't exist:
- Returns `null` instead of throwing exception

```php
$files = $request->getFile('nonexistent');
if ($files === null) {
    // Field doesn't exist
}
```

---

## Best Practices

### 1. Always Use Singleton

```php
// Good
$request = RequestObject::getRequestInstance();

// Avoid
$request = new RequestObject();
```

### 2. Validate Data

```php
$data = $request->getAllDatas();
$id = $data['id'] ?? null;

if ($id === null || !is_numeric($id)) {
    // Handle invalid data
}
```

### 3. Check for Files Before Processing

```php
$files = $request->getFile('upload');
if ($files !== null) {
    foreach ($files as $file) {
        // Process file
    }
}
```

### 4. Use Type Hints

```php
public function handleRequest(RequestObject $request): void
{
    $method = $request->getMethod();
    $data = $request->getAllDatas();
}
```

### 5. Handle Missing Data Gracefully

```php
$data = $request->getAllDatas();
$email = $data['email'] ?? '';
$name = $data['name'] ?? 'Guest';
```

---

## Performance Considerations

1. **Singleton Pattern:** Ensures only one instance, reducing memory usage
2. **Lazy Initialization:** Instance created only when first requested
3. **Data Caching:** All data extracted once during construction
4. **File Processing:** Files converted to objects during initialization

---

## Testing

### Unit Test Example

```php
use PHPUnit\Framework\TestCase;
use App\Kernel\RequestObject;

class RequestObjectTest extends TestCase
{
    public function testSingletonPattern()
    {
        $request1 = RequestObject::getRequestInstance();
        $request2 = RequestObject::getRequestInstance();
        $this->assertSame($request1, $request2);
    }

    public function testGetMethod()
    {
        $request = RequestObject::getRequestInstance();
        $this->assertIsString($request->getMethod());
    }

    public function testGetAllDatas()
    {
        $request = RequestObject::getRequestInstance();
        $data = $request->getAllDatas();
        $this->assertIsArray($data);
    }
}
```

---

## Related Classes

- **RouterObject** - Uses RequestObject to get URI for routing
- **AbstractController** - Receives RequestObject for handling requests
- **FileUpload** - Represents individual file uploads
- **FileFormator** - Normalizes file upload structure
- **AuthBearerMiddleware** - Uses RequestObject for authentication

---

## Changelog

### Version 1.0
- Initial implementation
- Singleton pattern
- Multi-source data merging
- File upload handling
- Session management
- Authorization header parsing

## Future Enhancements

- [ ] CSRF token handling
- [ ] Cookie handling


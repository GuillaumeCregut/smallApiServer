# Tools - Helpers, Dumpers & Response Management

## Overview

This document covers the utility tools in smallAPIServer that facilitate debugging, data serialization, and response handling. These components provide essential helpers for development and production environments.

**Location**: `Kernel\utils\` and `Kernel\Responses\`

**Key Components**:
- **Helpers**: Global utility functions for debugging and introspection
- **Dumper**: Static class for collecting and displaying dumped variables
- **DumpLine**: Data container for dump output
- **AbstractResponse**: Base class for all HTTP response types

## Helpers Functions

The `Helpers.php` file provides global functions for debugging and variable inspection. These are automatically available throughout your application.

### APP_DIR Constant

```php
define('APP_DIR', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
```

- **Purpose**: Defines the root application directory path
- **Usage**: Use this constant to reference the application root from within utilities
- **Example**:
  ```php
  require APP_DIR . 'vendor/autoload.php';
  ```

### dump() Function

**Purpose**: Display variable contents in readable HTML format

**Signature**:
```php
function dump(...$vars): void
```

**Parameters**:
- `...$vars` (mixed): One or more variables to display

**Behavior**:
- Collects variable information using backtrace
- Extracts variable name from source code
- Stores data in `DumpLine` objects
- Queues display via `Dumper::addLine()`

**Example**:
```php
$user = [
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com'
];
dump($user);
dump($user['id'], $user['name']);
```

**Output**: Styled HTML display with type, name, and value information

### dd() Function

**Purpose**: Dump variable and exit execution immediately

**Signature**:
```php
function dd(...$vars): never
```

**Parameters**:
- `...$vars` (mixed): One or more variables to display before exit

**Return Type**: `never` - Function always exits, never returns

**Behavior**:
- Displays backtrace information showing call location
- Uses `var_dump()` for variable display
- Applies dark theme HTML styling
- Exits with code 1 (error status)

**Example**:
```php
// Stop execution and inspect variable
$result = fetchFromDatabase();
dd($result); // Execution stops here

// Code below never runs
echo "This won't execute";
```

**Styling**: Dark theme with monospace font, suitable for terminal view

### ddjson() Function

**Purpose**: Dump variables in JSON format and exit

**Signature**:
```php
function ddjson(...$vars): void
```

**Parameters**:
- `...$vars` (mixed): One or more variables to output as JSON

**Behavior**:
- Sets `Content-Type: application/json` header
- Converts variables to JSON-serializable format
- Uses `convert_to_serializable()` for object handling
- Pretty-prints JSON with proper indentation
- Exits after output

**Example**:
```php
// API response for debugging
$data = ['status' => 'success', 'user_id' => 42];
ddjson($data);

// Output:
// {
//   "status": "success",
//   "user_id": 42
// }
```

**Use Case**: Quick API endpoint debugging, testing JSON serialization

### convert_to_serializable() Function

**Purpose**: Recursively convert any value to JSON-serializable format

**Signature**:
```php
function convert_to_serializable(
    mixed $value,
    int $depth = 0,
    array &$visited = []
): mixed
```

**Parameters**:
- `$value` (mixed): Value to convert
- `$depth` (int): Current recursion depth (prevents infinite recursion)
- `$visited` (array): Tracking visited object hashes

**Features**:
- **Scalar Values**: Passed through unchanged
- **Null Values**: Preserved as null
- **Arrays**: Recursively processes each element
- **Resources**: Converted to string representation `[Resource: type]`
- **Objects**:
  - Checks for `JsonSerializable` interface
  - Uses reflection on object properties (including private/protected)
  - Detects circular references
  - Returns object class name in `__class` field
- **Circular References**: Detected via `spl_object_hash()` tracking
- **Depth Limit**: Max 10 levels to prevent infinite recursion

**Example**:
```php
class User {
    private int $id = 1;
    protected string $name = "Alice";
    public array $metadata = ['role' => 'admin'];
}

$user = new User();
$serialized = convert_to_serializable($user);

// Result:
// [
//   '__class' => 'User',
//   'id' => 1,
//   'name' => 'Alice',
//   'metadata' => ['role' => 'admin']
// ]
```

**Edge Cases**:
- Circular references: Returns `[Circular Reference: ClassName]`
- Max depth exceeded: Returns `[Max depth reached]`
- Uninitialized properties: Returns `[Uninitialized]`

### getVarName() Function

**Purpose**: Extract variable name from source code line

**Signature**:
```php
function getVarName(string $file, int $line): string
```

**Parameters**:
- `$file` (string): File path to read
- `$line` (int): Line number to extract from

**Returns**: Variable name (e.g., `$myVar`) or `"null"` if not foun

**Behavior**:
- Reads source file to access actual code line
- Uses regex to extract variable name pattern `\$\w+`
- Used by `dump()` to label display output

**Example**:
```php
// In file.php at line 42:
$userName = "Alice";
dump($userName);

// getVarName() extracts: "$userName"
// Display shows: "Nom: $userName"
```

## Dumper Class

The `Dumper` class collects dump lines and manages their display.

**Location**: `Kernel\utils\Dumper.php`

**Namespace**: `App\Kernel\utils`

### Static Methods

#### addLine()

**Purpose**: Add a dump line to the collection

**Signature**:
```php
public static function addLine(DumpLine $line): void
```

**Parameters**:
- `$line` (DumpLine): Dump line object to add

**Example**:
```php
$dumpLine = new DumpLine('file.php:10', $value, 'string', '$myVar');
Dumper::addLine($dumpLine);
```

#### getLines()

**Purpose**: Retrieve all collected dump lines

**Signature**:
```php
public static function getLines(): array
```

**Returns**: Array of `DumpLine` objects

**Example**:
```php
$lines = Dumper::getLines();
foreach ($lines as $line) {
    echo $line->type . ": " . $line->name . "\n";
}
```

#### displayHTML()

**Purpose**: Render all dump lines as styled HTML

**Signature**:
```php
public static function displayHTML(): void
```

**Features**:
- Iterates through all collected dump lines
- Creates styled HTML div containers
- Displays type, name, line location, and value
- **Type-specific formatting**:
  - **Boolean**: Color-coded (green for true, red for false)
  - **Null**: Gray text
  - **String**: Shows length and escaped content in code block
  - **Array**: Shows count and formatted output
  - **Object**: Shows class name and properties
  - **Other**: Uses `var_dump()` output

**Styling**:
```
Background: #fff3cd (soft yellow)
Border: 2px solid #ff9800 (orange)
Header: Orange background with white text and bug emoji
Content area: White with border and monospace font
```

**Example Output**:
```html
<div style='background: #fff3cd; border: 2px solid #ff9800; ...'>
  <div style='background: #ff9800; ...'>🐛 DUMP at file.php:42</div>
  <div style='...'>
    <strong>Type:</strong> <span>array</span><br>
    <strong>Nom:</strong> <span>$users</span><br>
    <strong>Count:</strong> 3<br>
    <strong>Value:</strong><pre>...</pre>
  </div>
</div>
```

#### displayJSON()

**Purpose**: Output all dump lines as JSON

**Signature**:
```php
public static function displayJSON(): void
```

**Behavior**:
- Sets `Content-Type: application/json` header
- Converts each `DumpLine` to serializable format
- Outputs one JSON object per line
- Uses `convert_to_serializable()` for proper encoding

**Example**:
```json
{
  "line": "UserController.php:25",
  "value": {"id": 1, "name": "Alice"},
  "type": "array",
  "name": "$user"
}
```

## DumpLine Class

Simple data container for dump information.

**Location**: `Kernel\utils\DumpLine.php`

**Namespace**: `App\Kernel\utils`

### Properties

```php
public ?string $line;      // Location: "file.php:42"
public mixed $value;       // The actual value dumped
public ?string $type;      // Data type: "array", "string", "object", etc.
public ?string $name;      // Variable name: "$myVar"
```

### Constructor

**Signature**:
```php
public function __construct(
    ?string $line,
    mixed $value,
    ?string $type,
    ?string $name
)
```

**Parameters**: Correspond to class properties

**Example**:
```php
$dumpLine = new DumpLine(
    'UserController.php:25',
    ['id' => 1, 'name' => 'Alice'],
    'array',
    '$user'
);
```

## AbstractResponse Class

Base class for all HTTP response types with status codes, headers, and cookies.
### Debug Display

#### displayDump()

**Purpose**: Display collected debug information (abstract)

**Signature**:
```php
abstract protected function displayDump(): void
```

**Implementation Note**:
- Must be implemented by subclasses
- Typically calls `Dumper::displayHTML()` or `Dumper::displayJSON()`
- Only called when debug mode is enabled

**Debug Mode Detection**:
- Reads from environment via `GetEnvDatas::getEnvInstance()->get('DEBUG_MODE')`
- Only displays if debug mode is `true`
- Protected by try-catch for missing `Dumper` class

## Best Practices

### 1. Use dump() for Development Only

```php
// ✓ Good: Development debugging
if ($debug) {
    dump($user, $orders);
}

// ✗ Avoid: Keep in production code
dump($user); // Could reveal sensitive data
```

### 2. Prefer ddjson() for API Debugging

```php
// ✓ Good: JSON output for API testing
// In endpoint that fails
ddjson(['error' => 'Database connection failed']);

// ✗ Avoid: Using dd() in API endpoints
dd($query); // Breaks JSON response format
```

### 3. Use convert_to_serializable() for Complex Objects

```php
// ✓ Good: Safe serialization for APIs
$data = convert_to_serializable($complexObject);
echo json_encode($data);

// ✗ Avoid: Direct json_encode on objects
echo json_encode($complexObject); // May fail if object not JsonSerializable
```

### 4. Handle Circular References in Complex Objects

```php
// ✓ Good: Built-in circular reference detection
$user->friend = $user; // Circular reference
$data = convert_to_serializable($user); // Safely handles this

// ✗ Avoid: Not checking for circular references
json_encode($user); // May fail silently or cause issues
```

## Integration with Framework

### Helpers in Controllers

```php
class UserController extends AbstractController
{
    public function show(): void
    {
        $user = $this->userRepo->find(1);
        
        // Quick debug
        dump($user);
        
        // Stop execution and inspect
        dd($user);
        
        // Send as JSON debug
        ddjson($user);
    }
}
```

### Debug Mode Integration

```php
// .env
DEBUG_MODE=true

// Response automatically displays dumps when enabled
$response->send(); // Will show Dumper output if debug_mode=true
```

### Testing Helper

```php
// In unit tests
public function testUserData()
{
    $user = new User(['id' => 1, 'name' => 'Test']);
    
    // Check serialization
    $serialized = convert_to_serializable($user);
    $this->assertEquals(1, $serialized['id']);
    $this->assertEquals('Test', $serialized['name']);
}
```

## Complete Example

```php
<?php
// UserController.php
require 'Kernel/utils/Helpers.php';

class UserController extends AbstractController
{
    public function list()
    {
        try {
            $users = $this->userRepository->findAll();
            
            // Debug during development
            if (isset($_GET['debug'])) {
                dump($users);
            }
            
            // Send JSON response
            $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', 'application/json')
                ->setHeader('X-Total-Count', count($users))
                ->setCookie('view_time', time())
                ->setBody(json_encode($users))
                ->send();
                
        } catch (Exception $e) {
            // Error response
            ddjson(['error' => $e->getMessage()]);
        }
    }
    
    public function create()
    {
        $userData = $this->request->get();
        
        // Inspect data structure
        if ($this->debug) {
            dump($userData);
        }
        
        $user = $this->userRepository->create($userData);
        
        $this->response
            ->setStatusCode(201)
            ->setHeader('Location', '/users/' . $user->id)
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($user))
            ->send();
    }
}
```

## Troubleshooting

| Problem | Cause | Solution |
|---------|-------|----------|
| `dump()` shows no output | `Dumper` class not loaded | Ensure `use App\Kernel\utils\Dumper` at file top |
| Variable name shows as "null" | Variable not in standard `$var` format | Use explicit variable: `dump($myVar)` not expressions |
| `dd()` exits without output | Output buffering issue | Check for `ob_clean()` before `dd()` |
| JSON has `[Circular Reference]` | Object references itself | Expected behavior, handled safely |
| `convert_to_serializable()` deep recursion | Deeply nested objects/arrays | Check for circular references or reduce nesting |
| Debug output not displaying | Debug mode not enabled | Set `DEBUG_MODE=true` in `.env` |

## See Also

- [Response Classes](response.md) - JSON, HTML, and custom response types
- [Request](request.md) - Getting data from HTTP requests
- [Controller](controller.md) - Base controller with response integration
- [File Upload](fileUpload.md) - Handling uploaded files in requests

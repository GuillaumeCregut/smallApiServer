# Kernel Documentation
## Overview
The core application engine that routes requests to appropriate controllers
## Kernel Class

### Description

The `Kernel` class is the main application engine. It handles the complete request lifecycle from initialization through response generation. It manages routing, event dispatching, exception handling, and controller invocation.

### Location
`App\Kernel\Kernel`

### Key Responsibilities

1. Initialize environment and configuration
2. Parse incoming requests
3. Match routes to HTTP methods
4. Dispatch lifecycle events
5. Instantiate and execute controllers
6. Handle exceptions and errors

### Constructor

```php
public function __construct()
```

**Initialization Steps:**

1. **Load Environment Configuration**
   - Reads `.env` file from project root
   - Sets up `GetEnvDatas` singleton
   - Throws `KernelException` if environment file is missing

2. **Initialize Routes**
   - Loads route definitions from `Router::getRoutes()`

3. **Parse Request**
   - Uses `GetClientParams` to extract request data
   - Initializes `Request` singleton with:
     - Server variables (`$_SERVER`)
     - Input data (GET, POST, FILES)
     - Session data
     - Headers

4. **Extract Route URI**
   - Gets the requested URI from `$_SERVER`
   - Stores for route matching

5. **Initialize Event System**
   - Registers event listeners from configuration
   - Prepares event dispatcher for lifecycle events

**Example:**

```php
// Application initialization
$kernel = new Kernel();
$response = $kernel->route();
$output = $response->send();
```

### Methods

#### `route(): ResponseInterface`

Executes the complete request routing and controller execution lifecycle.

**Return Value:** `ResponseInterface` - The response object to send to client

**Process Flow:**

1. **Dispatch InitKernelEvent**
   - Allows listeners to perform pre-routing initialization
   - Example: Load configuration, initialize services

2. **Route Matching**
   ```
   Request URI → Check if route exists
              ↓
         If not found → 404 Response
              ↓
         If found → Check HTTP method
              ↓
         If method not allowed → 405 Response
              ↓
         Extract controller and method
   ```

3. **Dispatch Connector Event**
   - Initializes database connector
   - Allows listeners to set up database connections

4. **Dispatch Authentication Event**
   - Runs authentication middleware
   - Identifies and sets user information

5. **Dispatch API Key Check Event**
   - Validates API keys if needed
   - Allows custom access control

6. **Dispatch Controller Start Event**
   - Pre-controller execution hook
   - Last chance for middleware modifications

7. **Execute Controller**
   - Instantiates controller class
   - Calls matched controller method
   - Returns response

8. **Dispatch Return Response Event**
   - Post-response generation hook
   - Allows response modification or logging

9. **Error Handling**
   - Catches any exceptions during execution
   - Returns 500 error response with debug info if enabled

**Return Values:**

- **200-299**: Success response from controller
- **404**: Route not found
- **405**: HTTP method not allowed
- **500**: Server error during controller execution

**Example:**

```php
$kernel = new Kernel();

try {
    $response = $kernel->route();
    
    if ($response->getStatusCode() === 200) {
        echo "Request successful";
    } else if ($response->getStatusCode() === 404) {
        echo "Route not found";
    }
    
} catch (Exception $e) {
    echo "Application error: " . $e->getMessage();
}
```

### Event Lifecycle

The kernel dispatches events in this order:

```
1. InitKernelEvent
   ↓
2. ConnectorKernelEvent (database setup)
   ↓
3. CallAuthKernelEvent (authentication)
   ↓
4. CheckApiKeyKernelEvent (API validation)
   ↓
5. StartControllerKernelEvent (pre-execution)
   ↓
6. [Controller Execution]
   ↓
7. ReturnResponseKernelEvent (post-execution)
```

Each event can be listened to with custom middleware/listeners for cross-cutting concerns.

## Summary

| Component | Purpose |
|-----------|---------|
| **Kernel** | Application engine, request routing, lifecycle events |


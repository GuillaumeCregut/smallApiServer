# Documentation Summary

Welcome to the SmallMVC Framework documentation. This directory contains comprehensive guides for understanding and working with the core components of the framework.

## Quick Navigation

### Core Kernel Components

#### [RouterObject Documentation](./RouterObject.md)
The central routing dispatcher that handles HTTP request routing and controller dispatching.

**Key Topics:**
- Route configuration and management
- Request dispatching to controllers
- Error handling (404, 500)
- Middleware integration
- Best practices for routing

**When to use:** Understanding how requests are routed to controllers, adding new routes, debugging routing issues.

---

#### [RequestObject Documentation](./RequestObject.md)
A singleton class that encapsulates all HTTP request data and provides unified access to request information.

**Key Topics:**
- HTTP method handling (GET, POST, PUT, PATCH, DELETE)
- Data extraction from multiple sources
- File upload handling
- Session management
- Authorization header parsing

**When to use:** Accessing request data in controllers, handling file uploads, managing sessions, extracting authentication tokens.

---

#### [AbstractController Documentation](./AbstractController.md)
Abstract base class providing foundation for all controllers with built-in request handling and authentication.

**Key Topics:**
- Controller initialization and setup
- Authentication checking
- Error response handling
- Request data access
- RESTful CRUD patterns

**When to use:** Creating new controllers, implementing authentication checks, handling HTTP methods, returning responses.

---

#### [AbstractResponse Documentation](./AbstractResponse.md)
Abstract base class for HTTP response objects with status code, header, and body management.

**Key Topics:**
- Status code management
- HTTP header handling
- Response body formatting
- Response sending
- Built-in response classes

**When to use:** Understanding response handling, creating custom response types, managing HTTP headers and status codes.

---

#### [FileUpload Documentation](./FileUpload.md)
Class for handling file uploads with validation, security checks, and file movement operations.

**Key Topics:**
- File validation (size, MIME type)
- Secure file movement
- Error handling
- File information access
- Security best practices

**When to use:** Processing file uploads, validating file types and sizes, moving files to storage directories.

---

## Framework Architecture Overview

```
HTTP Request
    ↓
public/index.php (Entry Point)
    ↓
RouterObject (Route Dispatcher)
    ├─ Uses RequestObject (Get request data)
    ├─ Instantiates AuthBearerMiddleware
    └─ Calls appropriate Controller
    ↓
Controller (Business Logic)
    ├─ Uses RequestObject (Access request data)
    ├─ Uses Models (Database operations)
    └─ Returns ResponseInterface
    ↓
Response (JSON/Error)
    ↓
HTTP Response
```

## Documentation Structure

### By Component Type

**Kernel Components:**
- [RouterObject](./RouterObject.md) - Request routing and dispatching
- [RequestObject](./RequestObject.md) - HTTP request data handling
- [AbstractController](./AbstractController.md) - Base controller class
- [AbstractResponse](./AbstractResponse.md) - Base response class
- [FileUpload](./FileUpload.md) - File upload handling

**Controllers:**
- See `Controllers/` directory in main project

**Models:**
- See `Models/` directory in main project

**Services:**
- See `Services/` directory in main project

**Middleware:**
- See `middleware/` directory in main project

### By Use Case

**I want to...**

- **Add a new API endpoint**
  1. Read [RouterObject Documentation](./RouterObject.md) - Route Configuration section
  2. Create a new Controller
  3. Add route to RouterObject

- **Access request data in my controller**
  1. Read [RequestObject Documentation](./RequestObject.md) - Usage Examples section
  2. Use `RequestObject::getRequestInstance()`
  3. Call appropriate getter methods

- **Handle file uploads**
  1. Read [RequestObject Documentation](./RequestObject.md) - Working with Files section
  2. Use `$request->getFile()` or `$request->getFiles()`

- **Implement authentication**
  1. Read [RequestObject Documentation](./RequestObject.md) - Authentication section
  2. Use `$request->getAuthUser()` to extract Bearer token
  3. Validate token in middleware

- **Debug routing issues**
  1. Read [RouterObject Documentation](./RouterObject.md) - Error Handling section
  2. Check route configuration
  3. Verify controller exists and method is callable

- **Manage session data**
  1. Read [RequestObject Documentation](./RequestObject.md) - Session Management section
  2. Use `$request->getSessionValue()` and `$request->setSessionValue()`

- **Create a new controller**
  1. Read [AbstractController Documentation](./AbstractController.md) - Creating a Concrete Controller section
  2. Extend AbstractController
  3. Implement index() method
  4. Add route to RouterObject

- **Handle HTTP responses**
  1. Read [AbstractResponse Documentation](./AbstractResponse.md) - Usage Examples section
  2. Create response object (JsonResponse, ErrorResponse, etc.)
  3. Set status code and headers
  4. Set response body
  5. Return from controller

- **Process file uploads**
  1. Read [FileUpload Documentation](./FileUpload.md) - Usage Examples section
  2. Get files from request: `$request->getFile('field_name')`
  3. Validate files: `$file->isValid($maxSize, $allowedTypes)`
  4. Move files: `$file->move($directory, $name)`
  5. Handle exceptions

## Key Concepts

### Singleton Pattern
Both RouterObject and RequestObject use the singleton pattern to ensure only one instance exists throughout the application lifecycle.

**Benefits:**
- Single source of truth
- Memory efficient
- Consistent data access

### Request Data Merging
RequestObject intelligently merges data from multiple sources:
- Query parameters (`$_GET`)
- Form data (`$_POST`)
- JSON body
- URL path parameters (extracted as `id`)

**Merge Priority (for POST):**
1. `$_POST` (highest)
2. JSON body
3. `$_GET` (lowest)

### Middleware Integration
RouterObject automatically instantiates and injects `AuthBearerMiddleware` into controllers for authentication handling.

### Error Handling
The framework uses standardized HTTP status codes:
- **404** - Route not found
- **500** - Internal server error
- **401** - Unauthorized
- **422** - Unprocessable entity

## Common Tasks

### Adding a New Route

1. Open `Kernel/RouterObject.php`
2. Add entry to `$routes` array:
   ```php
   'users' => ['\App\Controllers\UserController', 'index'],
   ```
3. Create corresponding controller in `Controllers/`
4. Implement `index()` method returning `ResponseInterface`

See [RouterObject Documentation](./RouterObject.md#adding-new-routes) for details.

### Accessing Request Data

```php
$request = RequestObject::getRequestInstance();

// Get HTTP method
$method = $request->getMethod();

// Get all data
$data = $request->getAllDatas();

// Get specific value
$id = $data['id'] ?? null;
```

See [RequestObject Documentation](./RequestObject.md#usage-examples) for more examples.

### Handling File Uploads

```php
$request = RequestObject::getRequestInstance();

$files = $request->getFile('upload');
if ($files) {
    foreach ($files as $file) {
        // $file is a FileUpload object
    }
}
```

See [RequestObject Documentation](./RequestObject.md#working-with-files) for details.

### Extracting Authentication Token

```php
$request = RequestObject::getRequestInstance();

$auth = $request->getAuthUser();
if ($auth && $auth[0] === 'Bearer') {
    $token = $auth[1];
    // Validate token
}
```

See [RequestObject Documentation](./RequestObject.md#authentication) for details.

## Best Practices

### 1. Always Use Singleton Pattern
```php
// Good
$request = RequestObject::getRequestInstance();

// Avoid
$request = new RequestObject();
```

### 2. Validate Request Data
```php
$data = $request->getAllDatas();
$id = $data['id'] ?? null;

if ($id === null || !is_numeric($id)) {
    return new ClientErrorResponse(400);
}
```

### 3. Handle Errors Gracefully
```php
try {
    // Business logic
} catch (\Exception $e) {
    return new ErrorResponse(500);
}
```

### 4. Use Type Hints
```php
public function handleRequest(RequestObject $request): ResponseInterface
{
    // Implementation
}
```

### 5. Organize Routes Logically
```php
private array $routes = [
    '' => ['\App\Controllers\HomeController', 'index'],
    'api/users' => ['\App\Controllers\UserController', 'index'],
    'api/products' => ['\App\Controllers\ProductController', 'index'],
];
```

## Related Files

- **Main README:** See `../README.md` for project overview and setup instructions
- **Project Structure:** See `../` for complete project layout
- **Source Code:** See `../Kernel/` for actual implementation

## Troubleshooting

### 404 Error on Valid Route
- Check route is registered in RouterObject
- Verify controller class name and namespace
- Ensure controller file exists

### Request Data Not Found
- Check HTTP method (GET, POST, PUT, etc.)
- Verify data is being sent correctly
- Use `$request->getAllDatas()` to see all available data

### File Upload Not Working
- Check `maxsize` in `.env` configuration
- Verify form has `enctype="multipart/form-data"`
- Use `$request->getFile('field_name')` to access files

### Authentication Issues
- Verify Authorization header is being sent
- Check token format: `Authorization: Bearer <token>`
- Use `$request->getAuthUser()` to extract token

## Contributing to Documentation

When adding new documentation:
1. Follow the existing format and structure
2. Include code examples where applicable
3. Add links to related documentation
4. Update this README with new sections
5. Keep documentation in sync with code changes

## Version Information

- **Framework Version:** 1.0
- **Documentation Version:** 1.0
- **Last Updated:** 2024

## Additional Resources

- [Main Project README](../README.md) - Project overview and setup
- [Project Structure](../) - Complete directory layout
- [Source Code](../Kernel/) - Implementation details

---

**Need help?** Refer to the specific component documentation or check the troubleshooting section above.

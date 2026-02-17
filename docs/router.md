# Router Documentation

## Overview
Defines application routes and their corresponding controller methods
## Router Class

### Description

The `Router` class defines the application's URL routing configuration. It maps HTTP routes to specific controller methods and HTTP verbs.

### Location
`App\Kernel\Config\Router`

### Route Configuration

#### Format

```php
public static function getRoutes(): array
{
    return [
        'route_path' => [
            'HTTP_METHOD' => [ControllerClass::class, 'methodName'],
            // ...
        ],
        // ...
    ];
}
```

#### Route Rules

| Rule | Example | Valid |
|------|---------|-------|
| No leading slash | `user` | ✅ |
| No trailing slash | `user/profile` | ✅ |
| Root route | `''` (empty string) | ✅ |
| Leading slash | `/user` | ❌ |
| Trailing slash | `user/` | ❌ |

#### HTTP Methods Supported

- `GET`: Retrieve resources
- `POST`: Create resources
- `PUT`: Replace resources
- `PATCH`: Partially update resources
- `DELETE`: Remove resources

### Example Configuration

```php
class Router
{
    public static function getRoutes(): array
    {
        return [
            // Home/Root route: http://example.com/
            '' => [
                'GET' => [HomeController::class, 'getDatas'],
                'POST' => [HomeController::class, 'addData'],
                'PUT' => [HomeController::class, 'changeData'],
                'PATCH' => [HomeController::class, 'changeData'],
                'DELETE' => [HomeController::class, 'deleteData'],
            ],

            // User route: http://example.com/user
            'user' => [
                'GET' => [UserController::class, 'index'],
                'POST' => [UserController::class, 'create'],
                'PUT' => [UserController::class, 'update'],
                'DELETE' => [UserController::class, 'delete'],
            ],

            // API endpoint: http://example.com/api/products
            'api/products' => [
                'GET' => [ProductController::class, 'list'],
                'POST' => [ProductController::class, 'create'],
            ],

            // User profile: http://example.com/user/profile
            'user/profile' => [
                'GET' => [UserController::class, 'getProfile'],
                'PUT' => [UserController::class, 'updateProfile'],
            ],
        ];
    }
}
```

### Adding Routes

To add a new route:

1. **Open** `Kernel/Config/Router.php`
2. **Add entry** to the returned array
3. **Format**: `'route' => ['HTTP_METHOD' => [Controller::class, 'method']]`
4. **No leading/trailing slashes**

**Example: Add user profile route**

```php
'user/profile' => [
    'GET' => [UserController::class, 'getProfile'],
    'PUT' => [UserController::class, 'updateProfile'],
],
```

### URL-to-Route Mapping

| URL | Route Key | Controller | Method |
|-----|-----------|------------|--------|
| `http://example.com/` | `''` | HomeController | getDatas |
| `http://example.com/user` | `user` | UserController | index |
| `http://example.com/api/products` | `api/products` | ProductController | list |

### Error Responses

| Condition | Response | Reason |
|-----------|----------|--------|
| Route not in router | 404 | Route doesn't exist |
| HTTP method not defined for route | 405 | Method not allowed |
| Controller class not found | Exception | PHP class doesn't exist |
| Method doesn't exist | Exception | Method not defined |

## Summary

| Component | Purpose |
|-----------|---------|
| **Router** | URL-to-controller mapping configuration |
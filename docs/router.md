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

#### Route Parameters (Dynamic Routes)

Routes can include dynamic parameters using curly braces `{paramName}`. These parameters are extracted from the URL by the `RouteCompiler`:

```php
// Single parameter
'user/{id}' => [
    'GET' => [UserController::class, 'get'],
    'PUT' => [UserController::class, 'update'],
],

// Multiple parameters
'user/{id}/post/{postId}' => [
    'GET' => [UserController::class, 'getPost'],
],

// String slugs
'product/{slug}' => [
    'GET' => [ProductController::class, 'show'],
],
```

**Parameter Rules:**
- Placeholders match any value except forward slashes (`/`)
- Parameters are extracted and stored in `Request` data
- `{id}` parameter accessed via `$request->getData('id')`
- All parameters arrive as strings (convert to int if needed)

**Parameter Examples:**

| Route | URL | Extracted Parameters |
|-------|-----|---------------------|
| `user/{id}` | `/user/42` | `id => '42'` |
| `user/{id}/post/{postId}` | `/user/7/post/123` | `id => '7'`, `postId => '123'` |
| `product/{slug}` | `/product/my-item` | `slug => 'my-item'` |

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

            // User list: http://example.com/user
            'user' => [
                'GET' => [UserController::class, 'index'],
                'POST' => [UserController::class, 'create'],
            ],

            // Single user by ID: http://example.com/user/42
            'user/{id}' => [
                'GET' => [UserController::class, 'get'],
                'PUT' => [UserController::class, 'update'],
                'DELETE' => [UserController::class, 'delete'],
            ],

            // User's post: http://example.com/user/7/post/123
            'user/{id}/post/{postId}' => [
                'GET' => [UserController::class, 'getPost'],
                'DELETE' => [UserController::class, 'deletePost'],
            ],

            // Product by slug: http://example.com/product/my-awesome-item
            'product/{slug}' => [
                'GET' => [ProductController::class, 'show'],
                'PUT' => [ProductController::class, 'update'],
            ],
        ];
    }
}
```

**In the Controller, access parameters:**

```php
class UserController extends AbstractController
{
    public function get(): ResponseInterface
    {
        $id = $this->request->getData('id');  // From {id} placeholder
        $user = $this->repo->find($id);
        return $this->returnJson($user);
    }

    public function getPost(): ResponseInterface
    {
        $userId = $this->request->getData('id');      // From {id}
        $postId = $this->request->getData('postId');  // From {postId}
        
        $post = $this->repo->findUserPost($userId, $postId);
        return $this->returnJson($post);
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

| URL | Route Key | Parameters | Controller | Method |
|-----|-----------|-----------|------------|--------|
| `http://example.com/` | `''` | — | HomeController | getDatas |
| `http://example.com/user` | `user` | — | UserController | index |
| `http://example.com/user/42` | `user/{id}` | `id='42'` | UserController | get |
| `http://example.com/user/7/post/123` | `user/{id}/post/{postId}` | `id='7'`, `postId='123'` | UserController | getPost |
| `http://example.com/product/my-item` | `product/{slug}` | `slug='my-item'` | ProductController | show |

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

---

## Related Documentation

- [Request Documentation](./request.md) - How parameters are extracted from routes and accessed in controllers
- [RouteCompiler](./request.md#routecompiler) - Route pattern matching mechanism
- [Kernel](./kernel.md) - Application engine that processes routes and dispatches requests
- [Controller](./controller.md) - Build controllers that work with routed requests
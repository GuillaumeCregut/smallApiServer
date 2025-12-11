# SmallMVC - PHP REST API Framework

A lightweight, object-oriented PHP REST API framework with built-in authentication, routing, file handling, and database connectivity.
[Documentation](./docs/README.md)
## Features

- **RESTful Routing**: Clean routing system supporting GET, POST, PUT, PATCH, and DELETE methods
- **JWT Authentication**: Bearer token authentication middleware for secure API endpoints
- **Database Abstraction**: Connector service for database operations
- **File Upload Handling**: Built-in file upload and formatting utilities
- **Response Management**: Standardized JSON and error response handling
- **Middleware Support**: Authentication middleware for request validation
- **Unit Testing**: PHPUnit integration for testing
- **Environment Configuration**: Support for environment-based configuration via `.env` files

## Project Structure

```
attaquant/
├── Controllers/          # Application controllers handling business logic
├── Models/              # Data models for database operations
├── Services/            # Business logic services
│   ├── Responses/       # Response handlers (JSON, Error, ClientError)
│   └── Security/        # JWT token and authentication services
├── Kernel/              # Core framework components
│   ├── Files/           # File upload and formatting utilities
│   ├── Interfaces/      # Framework interfaces
│   ├── utils/           # Helper functions
│   ├── AbstractController.php
│   ├── AbstractResponse.php
│   ├── RequestObject.php
│   ├── RouterObject.php
│   └── GetEnvDatas.php
├── middleware/          # Request middleware (authentication, etc.)
├── Interfaces/          # Application interfaces
├── Security/            # User and security-related classes
├── public/              # Web root (index.php entry point)
├── tests/               # Unit tests
├── tools/               # Development tools (PHPUnit)
└── vendor/              # autoloader
```

## Requirements

- PHP 8.0 or higher
- MySQL/MariaDB or compatible database


## Installation

1. **Clone or download the project**
   ```bash
   git clone <repository-url>
   cd smallMVC
   ```

2. **Configure environment variables**
   - Copy `.env.sample` to `.env`
   - Update the configuration values:
     ```
     host = localhost          # Database host
     db = your_database        # Database name
     user = root               # Database user
     pass = password           # Database password
     secret = your_jwt_secret  # JWT secret key
     maxsize = 5242880         # Max upload file size in bytes
     ```

4. **Set up your web server**
   - Point your web root to the `public/` directory
   - Ensure `.htaccess` or web server configuration allows URL rewriting

## Usage

### Basic API Endpoint

The application uses a controller-based routing system. Controllers extend `AbstractController` and implement response interfaces.

**Example Controller Method:**
```php
public function index(): ResponseInterface
{
    switch ($this->request->getMethod()) {
        case 'GET':
            return $this->getDatas();
        case 'POST':
            return $this->addData();
        case 'PUT':
        case 'PATCH':
            return $this->changeData();
        case 'DELETE':
            return $this->deleteData();
        default:
            return $this->returnError(405);
    }
}
```

### Making Requests

**GET Request:**
```bash
curl http://localhost/api/endpoint
curl http://localhost/api/endpoint?id=1
```

**POST Request:**
```bash
curl -X POST http://localhost/api/endpoint \
  -H "Content-Type: application/json" \
  -d '{"key": "value"}'
```

**With Authentication (Bearer Token):**
```bash
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  http://localhost/api/endpoint
```

### Response Format

**Success Response (JSON):**
```json
{
  "status": 200,
  "data": {
    "id": 1,
    "name": "Example"
  }
}
```

**Error Response:**
```json
{
  "status": 404,
  "message": "Not Found"
}
```

## Authentication

The framework includes JWT (JSON Web Token) authentication via the `AuthBearerMiddleware`.

### Enabling Authentication

In your controller:
```php
public function index(): ResponseInterface
{
    if (!$this->isUserAuth()) {
        return $this->returnError(401);
    }
    // Continue with authenticated logic
}
```

### Generating Tokens

Use the `CreateJwtAuth` service to generate JWT tokens:
```php
$jwtService = new CreateJwtAuth();
$token = $jwtService->generateToken($userId, $secret);
```

## File Upload

The `FileUpload` class handles file uploads with validation and formatting.

**Configuration:**
- Set `maxsize` in `.env` to control maximum upload file size (in bytes)

**Usage:**
```php
$files = $this->request->getFiles();
$movedFile = $fileUpload->move(__DIR__ . '/../uploads/', $fileUpload->getName());
```

## Testing

Run unit tests using PHPUnit:

```bash
php tools/phpunit.phar --configuration tools/phpUnit.xml
```

Or use the batch file (Windows):
```bash
test.bat
```

## Database Models

Models extend the base model class and handle database operations:

```php
$model = new HomeModel($connector->getConnection());
$data = $model->getAll();
$single = $model->getOne($id);
$model->add($data);
$model->update($id, $data);
$model->delete($id);
```

## Error Handling

The framework provides standardized error responses:

- **400**: Bad Request
- **401**: Unauthorized
- **404**: Not Found
- **405**: Method Not Allowed
- **422**: Unprocessable Entity
- **500**: Internal Server Error

## Development

### Adding a New Controller

1. Create a new controller in `Controllers/` extending `AbstractController`
2. Implement the required methods
3. Register the route in the router

```php
private array $routes = [
        '' => ['\App\Controllers\HomeController', 'index',],
        'items' => ['\App\Controllers\ItemController', 'index',],
        'categories' => ['\App\Controllers\CategoryController', 'index',],
        'newRoute' =>['\App\Controllers\NewController','YOUR_METHOD'],
    ];
```

### Adding a New Model

1. Create a model in `Models/` extending the base model
2. Implement database query methods
3. Use in your controller

```php
private MyModel $model;

public function __construct(AuthenticationInterface $authMiddleware)
  {
      parent::__construct($authMiddleware);
      $this->connector = new Connector();
      $this->model = new MyModel($this->connector->getConnection());
  }

```


## Security Considerations

- Always validate and sanitize user input
- Use prepared statements for database queries
- Keep JWT secret secure and never commit to version control
- Use HTTPS in production
- Implement rate limiting for API endpoints
- Validate file uploads (type, size, content)

## License

[Add your license information here]

## Support

For issues or questions, please refer to the project documentation or contact the development team.

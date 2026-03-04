# SmallApiServer - Lightweight PHP MVC Framework

A modern, lightweight PHP MVC framework designed for building RESTful APIs and web applications. Built with clean architecture principles, extensive testing support, and comprehensive documentation.

**Status:** Active Development | **License:** MIT | **PHP Version:** 8.0+

---

## Table of Contents

- [Features](#features)
- [Quick Start](#quick-start)
- [Architecture](#architecture)
- [Project Structure](#project-structure)
- [Documentation](#documentation)
- [Installation](#installation)
- [Usage Examples](#usage-examples)
- [API Routes](#api-routes)
- [Testing](#testing)
- [Console Commands](#console-commands)
- [Contributing](#contributing)
- [License](#license)

---

## Features

✅ **Modern PHP Architecture**
- Built on PHP 8 with type safety
- Clean, maintainable code structure
- PSR-14 Event System integration
- Fluent interface patterns throughout

✅ **Database Abstraction**
- Repository Pattern implementation
- ORM-like entity management
- Automatic schema generation
- Query builder with parameter binding
- Hydrator for entity population

✅ **HTTP Request/Response Handling**
- Singleton Request class encapsulating all HTTP data
- Multiple response types (JSON, Error, Plain Text)
- File upload support
- Session and cookie management
- CSRF protection via referrer validation

✅ **Routing System**
- REST-ful routing configuration
- Automatic ID extraction from URLs
- HTTP method routing (GET, POST, PUT, DELETE, PATCH)
- Event-driven middleware support

✅ **Security**
- Password hashing support
- JWT token authentication ready
- Input validation framework
- CORS support
- API key authentication

✅ **Developer Experience**
- Console command system for automation
- Route debugging tools
- PHPUnit test integration
- Comprehensive documentation

---

## Quick Start

### 1. Installation

```bash
# Clone repository
git clone <repository-url>
cd smallApiServer


# Copy environment file
cp .env.sample .env

# Configure your .env file with database credentials
```

### 2. Start Development Server

```bash
# Start PHP server on port 9000
php -S localhost:9000 -t public

# Server runs at http://localhost:9000
```

### 3. Create Your First Endpoint

**1. Define a route** in `Kernel/Config/Router.php`:

```php
public static function getRoutes(): array
{
    return [
        'api/users' => [
            'GET' => [UserController::class, 'get'],
            'POST' => [UserController::class, 'add'],
            'PUT' => [UserController::class, 'update'],
            'DELETE' => [UserController::class, 'delete'],
        ],
    ];
}
```

**2. Create a Controller** at `Controllers/UserController.php`:

```php
<?php

namespace App\Controllers;

use App\Kernel\AbstractController;
use App\Kernel\Interfaces\ResponseInterface;

class UserController extends AbstractController
{
    public function get(): ResponseInterface
    {
        $id = $this->request->getData('id');
        
        if ($id) {
            // Return single user
            return $this->returnJson(['id' => $id, 'name' => 'John Doe']);
        }
        
        // Return all users
        return $this->returnJson([
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Jane Smith'],
        ]);
    }

    public function add(): ResponseInterface
    {
        $name = $this->request->getData('name');
        
        if (!$name) {
            return $this->returnError(422);  // Unprocessable Entity
        }

        // Save user
        return $this->returnJson(['id' => 3, 'name' => $name], 201);
    }
}
```

**3. Test the endpoint**:

```bash
# Get all users
curl http://localhost:9000/api/users

# Get user by ID
curl http://localhost:9000/api/users/1

# Create user
curl -X POST http://localhost:9000/api/users \
  -H "Content-Type: application/json" \
  -d '{"name": "Alice Johnson"}'
```

---

## Architecture

### Request Lifecycle

```
┌──────────────────────────────────────────────────────────────┐
│ Browser Request → public/index.php                           │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
        ┌────────────────────────────────────┐
        │ 1. Kernel Initialization           │
        │ • Load environment (.env)          │
        │ • Initialize Request singleton     │
        │ • Load routes configuration        │
        │ • Setup event system               │
        └────────────────────┬───────────────┘
                             │
                             ▼
        ┌────────────────────────────────────┐
        │ 2. Event: InitKernelEvent          │
        │ • Pre-routing initialization       │
        │ • Custom setup listeners           │
        └────────────────────┬───────────────┘
                             │
                             ▼
        ┌────────────────────────────────────┐
        │ 3. Route Matching                  │
        │ • Extract URI from request         │
        │ • Match against configured routes  │
        │ • Validate HTTP method             │
        └────────────────────┬───────────────┘
                             │
                             ▼
        ┌────────────────────────────────────┐
        │ 4. Events & Setup                  │
        │ • ConnectorKernelEvent (DB)        │
        │ • CallAuthKernelEvent (Auth)       │
        │ • CheckApiKeyKernelEvent (API Key) │
        └────────────────────┬───────────────┘
                             │
                             ▼
        ┌────────────────────────────────────┐
        │ 5. Controller Execution            │
        │ • Instantiate matched controller   │
        │ • Execute matched method           │
        │ • Get ResponseInterface object     │
        └────────────────────┬───────────────┘
                             │
                             ▼
        ┌────────────────────────────────────┐
        │ 6. Response Event                  │
        │ • ReturnResponseKernelEvent        │
        │ • Final listeners for response     │
        └────────────────────┬───────────────┘
                             │
                             ▼
        ┌────────────────────────────────────┐
        │ Response → Client                  │
        │ (JSON, HTML, Error, etc.)          │
        └────────────────────────────────────┘
```

### Core Components

| Component | Purpose |
|-----------|---------|
| **Kernel** | Main application engine, routes requests |
| **Router** | Defines URL routes and HTTP methods |
| **Controllers** | Handle business logic, return responses |
| **Request** | Singleton encapsulating all HTTP data |
| **Entities** | Data models representing business objects |
| **Repositories** | Data access layer for entities |
| **Event System** | PSR-14 middleware and hooks |
| **Console** | CLI commands for automation |

---

## Project Structure

```
smallApiServer/
├── bin/                         # Console commands
│   └── console.php              # CLI entry point
|
├── docs/                       # Documentation
│   ├── console.md              # Console system docs
│   ├── controller.md           # Controller guide
│   ├── request.md              # Request handling
│   ├── repository.md           # Repository pattern
│   ├── router.md               # Routing guide
│   ├── response.md             # Response types
│   ├── kernel.md               # Kernel documentation
│   └── ...
│
├── public/                      # Web root
│   ├── index.php                # Entry point
│   └── .htaccess                # Apache routing
|
|── src/
|   ├──Bin/
│   |   ├── AbstractConsole.php      
│   |   ├── ConsoleException.php      
│   |   ├── ConsoleInterface.php  
│   |   ├── Datababase.php        # Console for DB management 
│   |   ├── Debug.php             # Console for Debug  
│   |   ├── Debug/
│   |   |   └── Route.php         # Debug routes command
│   |   └── Datababase/        
│   |       └── Create.php        # Database create commands
│   |       └── CreateEntity.php  # Entity create command
│   |       └── CreateSql.php     # Create Entity table command
│   |
|   ├── Controllers/                  # Controllers
│   |   ├── HomeController.php
│   |   ├── UserController.php
│   |   └── ...
│   |
|   ├── Entity/                     # Business entities
│   |   └── ...
|   |
|   ├── Interfaces/
│   |   └── ...
|   |
|   ├── Kernel/                     # Core framework
|   │   ├── Kernel.php              # Main engine
|   │   ├── Request.php             # HTTP request handler
|   │   ├── AbstractController.php  # Controller base class
|   │   ├── Config/
|   │   │   ├── Router.php          # Route definitions
|   │   │   ├── DatabaseConnector.php
|   │   │   ├── Events.php          # Event configuration
|   │   │   └── ...
|   │   ├── Connector/
|   │   │   ├── AbstractEntity.php  # Entity base class
|   │   │   ├── AbstractRepository.php # Repository base class
|   │   │   ├── QueryBuilder.php    # Query building
|   │   │   ├── Hydrator.php        # Entity population
|   │   │   └── ...
|   │   ├── Responses/              # Response types
|   │   │   ├── JsonResponse.php
|   │   │   ├── ClientErrorResponse.php
|   │   │   └── ...
|   │   ├── Psr14/                  # Event system
|   │   │   ├── Dispatcher/
|   │   │   ├── Listener/
|   │   │   └── Events/
|   │   └── ...
│   |
|   ├── Repository/                 # Data repositories
│   |    └── ...
│   |
|   ├── Security/                   # Authentication
|   │   ├── User.php                # User entity
|   │   ├── UserRepository.php      # User data access
|   │   └── UserEntityInterface.php
|   │
|   |
|   └── Services/                   # Business logic
│       ├── Api/
│       ├── Mailer/
│       └── ...
│
├── tests/                      # Test suite
│   ├── bootstrap.php
│   ├── integration/
│   │   ├── RequestToResponse/
│   │   │   └── UserTest.php
│   │   └── kernel/
│   │       ├── RequestTest.php
│   │       └── database/
│   │           └── Request2EntityTest.php
│   └── unit/
|       └── ...
│
├── logs/                       # Application logs
│   ├── error
│   └── warning
│
├── vendor/                       
│   └── Autoload.php            # Custom autoloader
│
├── .env                        # Environment config
├── .env.sample                 # Example env
└── README.md                   # This file
```

---

## Documentation

Comprehensive documentation for all framework components:

### Core Documentation

| Document | Topic |
|----------|-------|
| [Kernel](./docs/kernel.md) | Application engine and request lifecycle |
| [Router](./docs/router.md) | Route configuration and HTTP methods |
| [Controller](./docs/controller.md) | Building controllers and handling responses |
| [Request](./docs/request.md) | HTTP request data and singleton pattern |
| [Response](./docs/response.md) | Response types and formatting |

### Data Layer Documentation

| Document | Topic |
|----------|-------|
| [Repository](./docs/repository.md) | Repository pattern and CRUD operations |
| [Hydrator](./docs/hydrator.md) | Entity population from arrays |
| [QueryBuilder](./docs/queryBuilder.md) | Building SQL queries |

### Advanced Documentation

| Document | Topic |
|----------|-------|
| [Console](./docs/console.md) | CLI commands and automation |
| [Authentication](./docs/autentication.md) | User authentication system |
| [JWT Tokens](./docs/jwtToken.md) | JWT token implementation |
| [API Client](./docs/apiClient.md) | HTTP API usage |
| [File Upload](./docs/fileUpload.md) | File handling |
| [Mailer](./docs/mailer.md) | Email services |
| [Events](./docs/event.md) | Event system and listeners |

---

## Installation

### Requirements

- PHP 8.0 or higher
- MySQL or PostgreSQL
- Apache with mod_rewrite (or equivalent)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd smallApiServer
   ```

2. **Configure environment**
   ```bash
   cp .env.sample .env
   # Edit .env with your database and app settings
   ```

3. **Create database**
   ```bash
   # Update database credentials in .env
   # Create the database in your MySQL/PostgreSQL
   ```

6. **Start development server**
   ```bash
   php -S localhost:9000 -t public
   ```

7. **Test the installation**
   ```bash
   curl http://localhost:9000/
   # Should return the home page response
   ```

---

## Usage Examples

### Create a RESTful API Endpoint

**Step 1: Define Route**

```php
// Kernel/Config/Router.php
'api/products' => [
    'GET' => [ProductController::class, 'list'],
    'POST' => [ProductController::class, 'create'],
],
'api/products' => [
    'GET' => [ProductController::class, 'show'],
    'PUT' => [ProductController::class, 'update'],
    'DELETE' => [ProductController::class, 'delete'],
],
```

**Step 2: Create Entity**

```php
// Entity/Product.php
namespace App\Entity;

use App\Kernel\Connector\AbstractEntity;

class Product extends AbstractEntity
{
    private ?string $name = null;
    private ?string $description = null;
    private ?float $price = null;

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): self { $this->name = $name; return $this; }

    public function getPrice(): ?float { return $this->price; }
    public function setPrice(?float $price): self { $this->price = $price; return $this; }
    
    // ... other getters/setters
}
```

**Step 3: Create Repository**

```php
// Repository/ProductRepository.php
namespace App\Repository;

use App\Kernel\Connector\AbstractRepository;
use App\Entity\Product;

class ProductRepository extends AbstractRepository
{
    protected ?string $entity = Product::class;

    public function findByMinPrice(float $minPrice): array
    {
        return $this->findBy(['min_price' => $minPrice]);
    }
}
```

**Step 4: Create Controller**

```php
// Controllers/ProductController.php
namespace App\Controllers;

use App\Kernel\AbstractController;
use App\Kernel\Connector\Hydrator;
use App\Entity\Product;
use App\Repository\ProductRepository;

class ProductController extends AbstractController
{
    private ProductRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new ProductRepository();
    }

    public function list(): ResponseInterface
    {
        $products = $this->repo->findAll();
        $data = [];
        
        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
            ];
        }
        
        return $this->returnJson($data);
    }

    public function create(): ResponseInterface
    {
        $productData = $this->request->getAllDatas();
        
        if (empty($productData['name']) || empty($productData['price'])) {
            return $this->returnError(422);
        }

        $product = Hydrator::hydrate(new Product(), $productData);
        $saved = $this->repo->save($product);

        return $this->returnJson([
            'id' => $saved->getId(),
            'name' => $saved->getName(),
        ], 201);
    }

    public function show(): ResponseInterface
    {
        $id = (int)$this->request->getData('id');
        $product = $this->repo->find($id);

        if (!$product) {
            return $this->returnError(404);
        }

        return $this->returnJson([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
        ]);
    }
}
```

### Using the Request Object

```php
// Access GET/POST data
$id = $this->request->getData('id');
$allData = $this->request->getAllDatas();

// Set custom data
$this->request->setData('processed', true);

// Get request method
if ($this->request->getMethod() === 'POST') {
    // Handle POST
}

// Access user (if authenticated)
if ($this->request->isConnected()) {
    $user = $this->request->getUser();
    echo "Hello " . $user->getUsername();
}

// Check CSRF (referrer validation)
if (!$this->request->isRefererValid()) {
    return $this->returnError(403);
}

// Handle file uploads
$files = $this->request->getFile('documents');
if ($files) {
    foreach ($files as $file) {
        $file->move('/uploads/');
    }
}
```

---

## API Routes

### Route Format

Routes are defined in `Kernel/Config/Router.php`:

```php
'user/profile' => [
    'GET' => [UserController::class, 'getProfile'],
    'PUT' => [UserController::class, 'updateProfile'],
],
```

### Current Endpoints

| Method | Route | Controller | Action |
|--------|-------|-----------|--------|
| GET | `user` | UserController | `get` |
| POST | `user` | UserController | `add` |
| PUT | `user` | UserController | `update` |
| DELETE | `user` | UserController | `delete` |
| GET | `home` | HomeController | `index` |

### URL ID Extraction

The framework automatically extracts numeric IDs from URL paths:

```bash
GET /user/123
# Automatically extracts id=123

GET /api/products/456/reviews/789
# Extracts id=456 (last numeric segment)
```

---

## Testing

### Run Test Suite

```bash
# Run all tests
./test.bat

# Run specific test file
./test.bat ./tests/integration/RequestToResponse/UserTest.php

```

### Example Test

```php
use App\Controllers\UserController;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testAddUser(): void
    {
        $server = ['REQUEST_METHOD' => 'POST'];
        $post = ['name' => 'John', 'username' => 'john'];
        
        Request::initInstance($server, [], [], $post, [], [], []);
        $controller = new UserController();
        $response = $controller->add();

        $this->assertEquals(201, $response->getStatusCode());
    }
}
```

---

## Console Commands

The framework includes a powerful console system for automation.

### View Available Commands

```bash
php ./bin/console.php help
```

### Debug Routes

```bash
# Display all configured routes
php ./bin/console.php debug route

# Display routes help
php ./bin/console.php debug route help
```

### Creating Custom Commands

Create a new command in `bin/` extending `AbstractConsole`:

```php
<?php

namespace App\bin;

class GenerateMigration extends AbstractConsole
{
    protected string $helpText = 'Generate database migration';
    protected int $minArgs = 1;

    public function execute(): void
    {
        $name = $this->args[0];
        echo "Generating migration: {$name}\n";
        // Generation logic
    }
}
```

Use it:

```bash
php ./bin/console.php generate:migration CreateUsersTable
```

For detailed console documentation, see [Console System](./docs/console.md).

---

## Environment Configuration

The `.env` file contains application configuration:

```ini
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=smallmvc
DB_USER=root
DB_PASS=


# Security
JWT_SECRET=your-secret-key-here
API_KEY=your-api-key

# Email
SMTP_HOST=smtp.mailtrap.io
SMTP_PORT=465
SMTP_USER=your-username
SMTP_PASS=your-password
```

---

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Write/update tests
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Code Standards

- Follow PSR-12 coding standard
- Write unit tests for new features
- Update documentation as needed
- Use meaningful commit messages

---

## Troubleshooting

### Routes not working (404 errors)

- Verify routes are defined in `Kernel/Config/Router.php`
- Check HTTP method matches (GET, POST, etc.)
- Ensure `.htaccess` is present in `public/` directory
- Verify Apache mod_rewrite is enabled

### Database connection issues

- Verify database credentials in `.env`
- Check database server is running
- Ensure database exists and user has permissions

### Permission errors

```bash
# Fix log directory permissions
chmod -R 777 logs/

# Fix vendor directory
chmod -R 755 vendor/
```

### Console commands not found

- Verify command file is in `bin/` directory
- Command class names should match file names (CamelCase)
- Ensure command implements `ConsoleInterface`

---

## Performance Optimization

### Production Checklist

- [ ] Set `DEBUG_MODE=false` in `.env`
- [ ] Use production-grade database server
- [ ] Set up log rotation
- [ ] Configure proper error handling
- [ ] Use a reverse proxy (Nginx)
- [ ] Enable gzip compression

```php
// Example: Nginx configuration
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/smallApiServer/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

---

## License

This project is licensed under the MIT License. See the [LICENCE](./LICENCE) file for details.

```
MIT License

Copyright (c) 2026 Guillaume Crégut

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:
```

---

## Resources

- **Documentation**: See `docs/` directory
- **Examples**: Check `Controllers/` and `tests/` directories
- **Source Code**: Explore `Kernel/` for core implementation

---

## Support

For issues, questions, or suggestions:

1. Check the [documentation](./docs/)
2. Search existing issues
3. Create a new issue with detailed information
4. Join the community discussions

---

## Changelog

### Version 1.0.0 (Current)

- Initial release
- Complete Request/Response cycle
- Repository and Entity system
- PSR-14 Event system
- Console command framework
- User authentication
- JWT token support
- File upload handling
- Comprehensive test suite

---

**Happy coding! 🚀**


# smallAPIServer Documentation

Welcome to the complete SmallAPI server framework documentation. This directory contains comprehensive guides for all framework components, organized by topic for easy navigation.

## Quick Navigation

- [📚 Getting Started](#getting-started)
- [🎯 Core Framework](#core-framework)
- [💾 Data Layer](#data-layer)
- [🌐 HTTP & Requests](#http--requests)
- [🔒 Security & Authentication](#security--authentication)
- [⚙️ Console & Commands](#console--commands)
- [🔧 Advanced Topics](#advanced-topics)
- [📊 Monitoring & Logging](#monitoring--logging)
- [🛠️ Tools & Utilities](#tools--utilities)
- [�📋 Configuration & Setup](#configuration--setup)

---

## Getting Started

New to SmallAPI server ? Start here:

1. **[Kernel Documentation](./kernel.md)** - Understand the application lifecycle and core engine
2. **[Router Documentation](./router.md)** - Learn how to define application routes
3. **[Controller Documentation](./controller.md)** - Build your first controllers
4. **[Request Documentation](./request.md)** - Handle HTTP requests
5. **[Response Documentation](./response.md)** - Generate HTTP responses

---

## Core Framework

### Fundamental Components

### [Kernel](./kernel.md)
The main application engine that orchestrates the entire request lifecycle.
- **Topics**: Request routing, event dispatching, controller invocation, error handling
- **Use when**: Understanding how the application processes requests
- **Key Classes**: `Kernel`, event system, lifecycle events

### [Router](./router.md)
Configuration for URL routing and HTTP method mapping.
- **Topics**: Route definition, REST patterns, ID extraction, HTTP methods
- **Use when**: Setting up new API endpoints or modifying routes
- **Key Classes**: `Router`, route configuration

### [Controller](./controller.md)
Base class for building application controllers.
- **Topics**: Request handling, response generation, data validation, error responses
- **Use when**: Creating new controller classes
- **Key Classes**: `AbstractController`, response types

### [Request](./request.md)
Singleton encapsulating all HTTP request data.
- **Topics**: GET/POST data access, file uploads, headers, cookies, sessions, user data
- **Use when**: Accessing request parameters and user information in controllers
- **Key Classes**: `Request`, data access methods

### [Response](./response.md)
Building and sending HTTP responses.
- **Topics**: JSON responses, error responses, status codes, content types
- **Use when**: Formatting responses to send to clients
- **Key Classes**: `ResponseInterface`, response implementations

---

## Data Layer

### Working with Databases and Entities

### [Repository](./repository.md)
Data access patterns: traditional Repository and modern EntityManager (Unit of Work).
- **Topics**: CRUD operations, entity management, query building, data persistence, Unit of Work pattern, identity maps
- **Use when**: Accessing and persisting entities to the database
- **Key Classes**: `AbstractRepository`, `RepositoryInterface`, `EntityManager`, `AbstractEntity`
- **Two Approaches**: Choose Repository for simple CRUD OR EntityManager for complex transactions (not both)
- **Related**: [Query Builder](./queryBuilder.md), [Hydrator](./hydrator.md)

### [Hydrator](./hydrator.md)
Automatic entity population from arrays.
- **Topics**: Property mapping, entity instantiation, data conversion
- **Use when**: Converting request data into entity objects
- **Key Classes**: `Hydrator`, entity conversion

### [Query Builder](./queryBuilder.md)
Building SQL queries programmatically.
- **Topics**: SELECT, INSERT, UPDATE, DELETE, WHERE clauses, parameter binding
- **Use when**: Constructing complex database queries
- **Key Classes**: `QueryBuilder`, query methods

### [MySQL Connector](./mysqlConnector.md)
Database connection management and query execution.
- **Topics**: Connection pooling, PDO integration, transaction management, prepared statements
- **Use when**: Understanding database connectivity and SQL execution
- **Key Classes**: `MySQLConnector`, `ConnectorInterface`
- **Related**: [Repository](./repository.md)

---

## HTTP & Requests

### Request and Response Handling

### [Request Documentation](./request.md)
Complete HTTP request handling.
- **Topics**: Data access, file uploads, sessions, cookies, user authentication
- **Use when**: Working with incoming HTTP requests
- **Key Methods**: `getData()`, `getFile()`, `getAllDatas()`

### [Response Documentation](./response.md)
Generating HTTP responses.
- **Topics**: JSON responses, status codes, headers, error handling
- **Use when**: Creating and sending responses to clients
- **Key Methods**: `returnJson()`, `returnError()`

### [File Upload](./fileUpload.md)
Handling file uploads from client requests.
- **Topics**: File validation, storage, security, file operations
- **Use when**: Processing uploaded files
- **Key Classes**: `FileUpload`, file handling methods

### [API Client](./apiClient.md)
Making HTTP requests to external APIs.
- **Topics**: HTTP requests, API integration, external services
- **Use when**: Integrating with third-party APIs
- **Key Methods**: HTTP client methods

---

## Security & Authentication

### User Authentication and Authorization

### [Authentication](./autentication.md)
User authentication system.
- **Topics**: Login, user validation, session management, user entity
- **Use when**: Implementing user login and session handling
- **Key Classes**: `User`, `UserRepository`, authentication methods

### [JWT Tokens](./jwtToken.md)
JWT token implementation for stateless authentication.
- **Topics**: Token creation, validation, claims, expiration
- **Use when**: Building token-based APIs
- **Key Methods**: Token generation, validation

### [Create JWT Token](./createJwtToken.md)
Detailed guide for creating JWT tokens.
- **Topics**: Token structure, signing, encoding, best practices
- **Use when**: Setting up token authentication
- **Code Examples**: Complete token creation examples

### [Get Environment Data](./getEnv.md)
Environmental configuration and credentials management.
- **Topics**: .env file usage, configuration loading, secrets management
- **Use when**: Accessing application configuration
- **Key Classes**: `GetEnvDatas`

---

## Console & Commands

### Command-Line Tools and Automation

### [Console System](./console.md)
Building and executing console commands.
- **Topics**: Command creation, argument handling, sub-commands, output formatting
- **Use when**: Creating CLI tools and automation scripts
- **Key Classes**: `AbstractConsole`, `ConsoleInterface`, `ConsoleHelper`
- **Example**: Debug commands, migrations, seeding

---

## Advanced Topics

### In-Depth Framework Features

### [Event System](./event.md)
PSR-14 compliant event system for middleware and hooks.
- **Topics**: Event listeners, event dispatching, lifecycle events, custom events
- **Use when**: Creating event listeners or handling application events
- **Key Classes**: `EventDispatcher`, event classes

### [Mailer Service](./mailer.md)
Email service integration and sending.
- **Topics**: Email composition, sending, SMTP configuration, templates
- **Use when**: Implementing email functionality
- **Key Classes**: `Mailer`, email methods

### [Home Controller](./homecontroller.md)
Example implementation of the home page controller.
- **Topics**: Controller examples, response generation, page rendering
- **Use when**: Understanding controller patterns
- **Use as**: Reference implementation

---

## Monitoring & Logging

### Application Monitoring and Event Logging

### [Logger](./logger.md)
Centralized logging system for application events and errors.
- **Topics**: Error logging, warning logs, info logs, debug mode, log files, file creation
- **Use when**: Recording application events, debugging, error tracking
- **Key Classes**: `Logger` (static utility)
- **Log Levels**: ERROR, WARNING, INFO

---

## Tools & Utilities

### Development and Debugging Tools

### [Tools & Utilities](./tools.md)
Debugging, dumping, and response helper utilities.
- **Topics**: Variable dumping, debug output, serialization, response management
- **Use when**: Debugging in development or building response objects
- **Key Classes**: `Dumper`, `DumpLine`, `AbstractResponse`
- **Key Functions**: `dump()`, `dd()`, `ddjson()`, `convert_to_serializable()`

---

## Configuration & Setup

### Application Configuration

### [Get Environment Data](./getEnv.md)
Managing application configuration from environment files.
- **Topics**: .env configuration, credentials, environment-specific settings
- **Use when**: Setting up application configuration
- **File Format**: `.env` file structure

---

## Documentation Map

### By Topic

| Topic | Files |
|-------|-------|
| **Core Framework** | Kernel, Router, Controller |
| **Data Access** | Repository, Hydrator, QueryBuilder, MySQLConnector |
| **HTTP Handling** | Request, Response, FileUpload, ApiClient |
| **Security** | Authentication, JWT Tokens |
| **Automation** | Console System |
| **Advanced** | Event System, Mailer |
| **Monitoring** | Logger |
| **Utilities** | Tools (Helpers, Dumper, Response) |
| **Configuration** | GetEnv |

### By Use Case

**Building a REST API endpoint:**
1. [Router](./router.md) - Define the route
2. [Controller](./controller.md) - Create the controller
3. [Request](./request.md) - Access request data
4. [Repository](./repository.md) - Query the database
5. [Response](./response.md) - Send the response

**Adding user authentication:**
1. [Authentication](./autentication.md) - User login
2. [JWT Tokens](./jwtToken.md) - Token-based auth
3. [Get Environment Data](./getEnv.md) - Store secrets

**Uploading and processing files:**
1. [Request](./request.md) - Receive file upload
2. [File Upload](./fileUpload.md) - Handle the file
3. [Response](./response.md) - Return result

**Creating automation tools:**
1. [Console System](./console.md) - Build command
2. [Get Environment Data](./getEnv.md) - Access config

---

## File Organization

```
docs/
├── README.md                    # This file
├── kernel.md                    # Core application engine
├── router.md                    # Route configuration
├── controller.md                # Controller base class
├── request.md                   # HTTP request handling
├── response.md                  # HTTP response generation
├── repository.md                # Data repository pattern
├── hydrator.md                  # Entity hydration
├── queryBuilder.md              # SQL query builder
├── fileUpload.md                # File upload handling
├── apiClient.md                 # External API client
├── autentication.md             # User authentication
├── jwtToken.md                  # JWT token system
├── createJwtToken.md            # JWT creation guide
├── event.md                     # Event system
├── mailer.md                    # Email service
├── getEnv.md                    # Configuration management
├── mysqlConnector.md            # MySQL database connector
├── console.md                   # Console commands
├── logger.md                    # Application logging system
├── tools.md                     # Helper utilities and debugging
└── homecontroller.md            # Example controller
```

---

## Learning Paths

### Path 1: REST API Development (Recommended for most developers)

1. Start: [Router](./router.md) - Learn route definition
2. Continue: [Controller](./controller.md) - Build your first controller
3. Deep dive: [Request](./request.md) - Master request handling
4. Add data: [Repository](./repository.md) - Connect to database
5. Format output: [Response](./response.md) - Send responses
6. Secure it: [Authentication](./autentication.md) - Add user auth

**Time estimate**: 1-2 days

### Path 2: Full Application Development

1. Foundation: [Kernel](./kernel.md) - Understand the system
2. Routes: [Router](./router.md) - Define API routes
3. Controllers: [Controller](./controller.md) - Build logic
4. Data: [Repository](./repository.md) → [Hydrator](./hydrator.md) → [QueryBuilder](./queryBuilder.md) → [MySQL Connector](./mysqlConnector.md)
5. Requests: [Request](./request.md) - Handle input
6. Responses: [Response](./response.md) - Send output
7. Files: [FileUpload](./fileUpload.md) - Handle uploads
8. Users: [Authentication](./autentication.md) + [JWT Tokens](./jwtToken.md)
9. Logging: [Logger](./logger.md) - Track events
10. Advanced: [Event System](./event.md) - Add middleware
11. Tools: [Console System](./console.md) - Automation

**Time estimate**: 1 week

### Path 3: API Integration

1. Quick start: [Request](./request.md) - Understand requests
2. External APIs: [API Client](./apiClient.md) - Call external services
3. Responses: [Response](./response.md) - Format responses
4. Auth: [JWT Tokens](./jwtToken.md) - Secure your API
5. Config: [Get Environment Data](./getEnv.md) - Manage credentials

**Time estimate**: 1 day

### Path 4: Advanced Framework Customization

1. Core: [Kernel](./kernel.md) - Understand the engine
2. Events: [Event System](./event.md) - Add custom listeners
3. Services: [Mailer](./mailer.md) - Extend services
4. Tools: [Console System](./console.md) - Create commands
5. Configuration: [Get Environment Data](./getEnv.md) - Manage settings

**Time estimate**: 2-3 days

---

## Quick Reference

### Controllers - Return Responses

```php
$this->returnJson($data, 200);       // JSON response
$this->returnError(404);              // Error response
```

See: [Response](./response.md), [Controller](./controller.md)

### Requests - Access Data

```php
$this->request->getData('id');        // Single value
$this->request->getAllDatas();        // All data
$this->request->getFile('upload');    // File upload
```

See: [Request](./request.md)

### Database - CRUD Operations

```php
$repo->find($id);                     // Read
$repo->save($entity);                 // Create/Update
$repo->delete($entity);               // Delete
$repo->findAll();                     // List all
```

See: [Repository](./repository.md)

### Authentication

```php
$user = $this->request->getUser();    // Get user
if ($this->request->isConnected()) {} // Check auth
```

See: [Authentication](./autentication.md)

### Tokens

```php
$token = JwtToken::create($claims);   // Create
JwtToken::verify($token);             // Verify
```

See: [JWT Tokens](./jwtToken.md)

### Console Commands

```bash
php ./bin/console.php command:subcommand arg1 arg2
```

See: [Console System](./console.md)

---

## Tips and Best Practices

### ✅ Do's

- ✅ Use Repository pattern for data access
- ✅ Use Hydrator to populate entities
- ✅ Validate request data before using
- ✅ Use JWT for stateless authentication
- ✅ Store secrets in environment variables
- ✅ Check user authentication before operations
- ✅ Use proper HTTP status codes
- ✅ Return consistent JSON responses

### ❌ Don'ts

- ❌ Don't access database directly in controllers
- ❌ Don't expose sensitive information in responses
- ❌ Don't hardcode configuration values
- ❌ Don't skip input validation
- ❌ Don't use global variables
- ❌ Don't ignore error handling
- ❌ Don't skip authentication checks
- ❌ Don't commit secrets to version control

---

## Troubleshooting

### Can't find documentation on...

| Topic | Try this document |
|-------|-------------------|
| How routes work | [Router](./router.md) |
| Building controllers | [Controller](./controller.md) |
| Getting request data | [Request](./request.md) |
| Sending responses | [Response](./response.md) |
| Database operations | [Repository](./repository.md) |
| Database connection | [MySQL Connector](./mysqlConnector.md) |
| Query building | [Query Builder](./queryBuilder.md) |
| User login | [Authentication](./autentication.md) |
| API tokens | [JWT Tokens](./jwtToken.md) |
| File uploads | [FileUpload](./fileUpload.md) |
| Automation tasks | [Console System](./console.md) |
| Custom hooks | [Event System](./event.md) |
| Logging events | [Logger](./logger.md) |
| Debugging variables | [Tools & Utilities](./tools.md) |


---

## Additional Resources

### In Repository

- **README.md** - Project overview and quick start
- **Tests** - See `tests/` directory for usage examples
- **Controllers** - See `Controllers/` directory for examples
- **Kernel** - See `Kernel/` directory for implementation

### External Resources

- PHP Official: https://www.php.net/
- PSR Standards: https://www.php-fig.org/psr/
- REST API Best Practices: https://restfulapi.net/
- JWT Documentation: https://jwt.io/

---

## Document Status

| Document | Status | Last Updated |
|----------|--------|--------------|
| kernel.md | ✅ Complete | 2026-02-17 |
| router.md | ✅ Complete | 2026-02-17 |
| controller.md | ✅ Complete | 2026-02-17 |
| request.md | ✅ Complete | 2026-02-17 |
| response.md | ✅ Complete | 2026-02-17 |
| repository.md | ✅ Complete | 2026-02-17 |
| hydrator.md | ✅ Complete | 2026-02-17 |
| queryBuilder.md | ✅ Complete | 2026-02-17 |
| mysqlConnector.md | ✅ Complete | 2026-02-17 |
| fileUpload.md | ✅ Complete | 2026-02-17 |
| apiClient.md | ✅ Complete | 2026-02-17 |
| autentication.md | ✅ Complete | 2026-02-17 |
| jwtToken.md | ✅ Complete | 2026-02-17 |
| createJwtToken.md | ✅ Complete | 2026-02-17 |
| console.md | ✅ Complete | 2026-02-17 |
| event.md | ✅ Complete | 2026-02-17 |
| mailer.md | ✅ Complete | 2026-02-17 |
| getEnv.md | ✅ Complete | 2026-02-17 |
| homecontroller.md | ✅ Complete | 2026-02-17 |
| logger.md | ✅ Complete | 2026-02-17 |
| tools.md | ✅ Complete | 2026-02-17 |

---

## Contributing to Documentation

To improve or add documentation:

1. Create a Markdown file in this directory
2. Follow the structure of existing documents
3. Include code examples with proper syntax highlighting
4. Add a link to this README
5. Update the file organization section

---

## Quick Links

- 🏠 Main README: [../README.md](../README.md)
- 🔧 Source Code: [../Kernel](../Kernel)
- 📋 Tests: [../tests](../tests)
- 📝 Configuration: [../.env.sample](../.env.sample)
- 🚀 Console: [../bin](../bin)

---

**Last Updated**: February 17, 2026

For questions or improvements, please refer to the repository's issue tracker.


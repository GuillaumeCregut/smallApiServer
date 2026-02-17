# Repositories and Entities

## Overview

The Repository and Entity system of smallApiServer implements the Repository pattern, which separates data persistence logic from business logic. **Entities** represent business objects, while **Repositories** manage their persistence to the database.

---

## Entities

### Concept

An Entity is a class that represents a business object with its data and properties. It implements the `EntityInterface` and typically inherits from `AbstractEntity`.

### AbstractEntity

The `AbstractEntity` abstract class provides basic functionality:

```php
abstract class AbstractEntity implements EntityInterface
{
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }
}
```

**Features:**
- Manages the unique identifier (`id`) of the entity
- Uses the Fluent Interface pattern (returns `$this`)
- Can be extended with additional business methods

### Example: User Entity

```php
namespace App\Security;

use App\Kernel\Connector\AbstractEntity;
use App\Kernel\Connector\Attributes\NotStored;
use App\Kernel\Connector\Attributes\Nullable;

class User extends AbstractEntity implements UserEntityInterface
{
    private array $roles = [];
    private ?string $name = null;
    private ?string $firstname = null;
    private ?string $username = null;
    private ?string $password = null;
    
    #[Nullable]
    private ?string $token = null;
    
    #[NotStored]
    private ?string $newpassword = null;

    // Getters and Setters
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    // ... other properties
}
```

### Attributes

#### @Nullable
Indicates that a property can be `NULL` in the database.

```php
#[Nullable]
private ?string $token = null;
```

#### @NotStored
Indicates that a property should **not** be saved to the database. Useful for intermediate or temporary fields.

```php
#[NotStored]
private ?string $newpassword = null;  // Intermediate field for password input
```

### Naming Conventions

- Properties in camelCase are automatically converted to snake_case in the database:
  - `firstName` → `first_name`
  - `username` → `username`
  - `id` → `id`

- Table names are derived from class names:
  - `User` → `users`
  - `Product` → `products`
  - `UserEntity` → `users` (the `Entity` suffix is removed)

---

## Repositories

### Concept

A Repository is a class that encapsulates data access logic. It provides an interface for performing CRUD (Create, Read, Update, Delete) operations on entities.

### AbstractRepository

The `AbstractRepository` abstract class implements `RepositoryInterface` and provides base methods:

```php
abstract class AbstractRepository implements RepositoryInterface
{
    protected ConnectorInterface $connector;
    protected ?string $entity = null;
    protected QueryBuilder $qb;
    // ... internal properties
}
```

### Example: UserRepository

```php
namespace App\Security;

use App\Kernel\Connector\AbstractRepository;
use App\Kernel\Interfaces\Databases\EntityInterface;

class UserRepository extends AbstractRepository
{
    protected ?string $entity = User::class;

    public function findOneByEmail(string $email): ?EntityInterface
    {
        $search = ['email' => $email];
        $result = $this->findBy($search);
        if (empty($result)) {
            return null;
        }
        if (1 < count($result)) {
            throw new DatabaseException('More than one user found');
        }
        return $result[0];
    }

    public function findByUserNameCredentials(string $username, string $password): ?User
    {
        $search = ['username' => $username];
        $result = $this->findBy($search);
        if (empty($result)) {
            return null;
        }
        $user = $result[0];
        
        // Verify password
        if (!password_verify($password, $user->getPassword())) {
            return null;
        }
        return $user;
    }

    public function save(EntityInterface $entity): null | false | EntityInterface
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException('Entity must be User');
        }

        // Handle password hashing if a new password is set
        if (null !== $entity->getNewPassword()) {
            $hashedPassword = password_hash($entity->getNewPassword(), PASSWORD_DEFAULT);
            $entity->setPassword($hashedPassword);
            $entity->setNewPassword(null);
        }

        return parent::save($entity);
    }
}
```

### Main Methods

#### find(int $id): ?EntityInterface

Retrieves an entity by its identifier.

```php
$repo = new UserRepository();
$user = $repo->find(1);  // Returns the user with id=1 or null
```

#### findBy(array $fields): array

Retrieves all entities matching the provided criteria.

```php
$users = $repo->findBy(['username' => 'jsmith']);
```

#### findAll(): array

Retrieves all entities.

```php
$allUsers = $repo->findAll();
```

#### save(EntityInterface $entity): ?EntityInterface | false

Creates or updates an entity:
- If the entity has no ID (`getId()` returns `null`), it is **inserted** (CREATE)
- If the entity has an ID, it is **updated** (UPDATE)

```php
$user = new User();
$user->setName('Doe');
$user->setFirstname('John');
$user->setUsername('jdoe');
$user->setNewPassword('secure123');

$savedUser = $repo->save($user);  // INSERT
$savedUser->setName('Smith');
$repo->save($savedUser);  // UPDATE
```

**Return Values:**
- The saved entity (with ID set if INSERT)
- `null` if error during save
- `false` if query fails

#### delete(EntityInterface $entity): bool

Deletes an entity.

```php
$user = $repo->find(1);
if ($user) {
    $repo->delete($user);  // DELETE
}
```

---

## Usage in Controllers

### Example: UserController

```php
namespace App\Controllers;

use App\Security\User;
use App\Security\UserRepository;
use App\Kernel\AbstractController;
use App\Kernel\Connector\Hydrator;
use App\Kernel\Interfaces\ResponseInterface;

class UserController extends AbstractController
{
    private UserRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new UserRepository();
    }

    // CREATE - Add a new user
    public function add(): ResponseInterface
    {
        $userDatas = $this->request->getAllDatas();

        // Create a new User entity
        $user = Hydrator::hydrate(new User(), $userDatas);
        $user->addRole('USER');
        $user->setNewPassword($user->getPassword());
        $user->setPassword(null);

        // Save via repository
        $savedUser = $this->repo->save($user);
        if (!$savedUser) {
            return $this->returnError(500);
        }

        return $this->returnJson([
            'id' => $savedUser->getId(),
            'name' => $savedUser->getName(),
            'firstname' => $savedUser->getFirstname(),
            'username' => $savedUser->getUsername(),
        ], 201);
    }

    // READ - Retrieve a user or all users
    public function get(): ResponseInterface
    {
        $id = $this->request->getData('id') ?? null;
        
        if ($id) {
            $user = $this->repo->find($id);
            if (null === $user) {
                return $this->returnError(404);
            }

            return $this->returnJson([
                'id' => $user->getId(),
                'name' => $user->getName(),
                'firstname' => $user->getFirstname(),
            ]);
        }

        // Return all users
        $users = $this->repo->findAll();
        $returnArray = [];
        foreach ($users as $user) {
            $returnArray[] = [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'firstname' => $user->getFirstname(),
            ];
        }
        return $this->returnJson($returnArray);
    }

    // UPDATE - Update a user
    public function update(): ResponseInterface
    {
        $id = $this->request->getData('id') ?? 0;
        $userDatas = $this->request->getAllDatas();

        $user = $this->repo->find($id);
        if (null === $user) {
            return $this->returnError(404);
        }

        // Update properties
       

        $result = $this->repo->save($user);
        if (!$result) {
            return $this->returnError(500);
        }

        return $this->returnJson(null, 204);
    }

    // DELETE - Delete a user
    public function delete(): ResponseInterface
    {
        $id = $this->request->getData('id') ?? 0;
        
        $user = $this->repo->find($id);
        if (null === $user) {
            return $this->returnError(404);
        }

        $this->repo->delete($user);
        return $this->returnJson(null, 204);
    }
}
```

---

## Hydrator

The `Hydrator` fills entity properties from an array of data.

```php
use App\Kernel\Connector\Hydrator;

$userData = [
    'name' => 'Smith',
    'firstname' => 'John',
    'username' => 'jsmith'
];

$user = new User();
Hydrator::hydrate($user, $userData);

// $user->getName() === 'Smith'
// $user->getFirstname() === 'John'
// $user->getUsername() === 'jsmith'
```

---

## Testing

Tests integrate repositories and entities to validate the complete flow.

### Example: UserTest.php

```php
use App\Controllers\UserController;
use App\Security\UserRepository;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private static int $userId = 0;

    public function testAddUser(): void
    {
        // Initialize connector
        GetEnvDatas::getEnvInstance($envFile);
        $connector = MySQLConnector::getInstance([...]);
        ConnectorDispatcher::setConnector($connector);

        // Prepare request
        $server = ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/user'];
        $post = [
            'name' => 'London',
            'firstname' => 'Jack',
            'password' => '1234',
            'username' => 'jlondon'
        ];
        Request::initInstance($server, [], [], $post, [], [], [], []);

        // Execute
        $controller = new UserController();
        $response = $controller->add();
        $data = json_decode($response->getBody(), true);

        // Verify
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertIsArray($data);
        self::$userId = $data['id'];
    }

    #[Depends('testAddUser')]
    public function testGetFound(): void
    {
        // Retrieve created user
        $server = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/user'];
        $get = ['id' => self::$userId];
        Request::initInstance($server, [], $get, [], [], [], [], []);

        $controller = new UserController();
        $response = $controller->get();
        $data = json_decode($response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($data);
    }

    #[Depends('testGetFound')]
    public function testUpdateUser(): void
    {
        // Update user
        $post = [
            'id' => self::$userId,
            'name' => 'New Name',
            'firstname' => 'New Firstname'
        ];
        $server = ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/user'];
        Request::initInstance($server, [], [], $post, [], [], [], []);

        $controller = new UserController();
        $response = $controller->update();

        $this->assertEquals(204, $response->getStatusCode());
    }

    #[Depends('testUpdateUser')]
    public function testDeleteUser(): void
    {
        // Delete user
        $post = ['id' => self::$userId];
        $server = ['REQUEST_METHOD' => 'DELETE', 'REQUEST_URI' => '/user'];
        Request::initInstance($server, [], [], $post, [], [], [], []);

        $controller = new UserController();
        $response = $controller->delete();

        $this->assertEquals(204, $response->getStatusCode());

        // Verify user is deleted
        $response = $controller->get();
        $this->assertEquals(404, $response->getStatusCode());
    }
}
```

---

## Best Practices

### 1. Business Logic in Repository

Centralize complex search logic in repository methods:

```php
// ✓ GOOD
public function findActiveUsersByRole(string $role): array
{
    return $this->findBy(['role' => $role, 'active' => true]);
}

// ✗ BAD - Logic scattered in controller
```

### 2. Data Validation

Validate entities before saving:

```php
if (!$this->isValidUser($user)) {
    return $this->returnError(422);  // Unprocessable Entity
}
$this->repo->save($user);
```

### 3. Error Handling

Handle repository exceptions:

```php
try {
    $user = $this->repo->save($user);
    if (!$user) {
        throw new DatabaseException("Failed to save user");
    }
} catch (DatabaseException $e) {
    return $this->returnError(500);
}
```

### 4. Sensitive Properties

Use `#[NotStored]` for temporary properties like password input:

```php
#[NotStored]
private ?string $newpassword = null;

// In repository's save method
if (null !== $entity->getNewPassword()) {
    $hashedPassword = password_hash($entity->getNewPassword(), PASSWORD_DEFAULT);
    $entity->setPassword($hashedPassword);
}
```

### 5. Using Hydrator

Use `Hydrator` to populate entities from received data:

```php
$user = Hydrator::hydrate(new User(), $requestData);
```

### 6. Strong Typing

Use type hints to improve clarity:

```php
public function save(EntityInterface $entity): null | false | EntityInterface
{
    if (!$entity instanceof User) {
        throw new InvalidArgumentException('Entity must be User');
    }
    // ...
}
```

---

## Complete CRUD Operation Flow

### Creating a User

```php
// 1. Retrieve request data
$userData = $request->getAllDatas();

// 2. Create new entity
$user = Hydrator::hydrate(new User(), $userData);
$user->setNewPassword($user->getPassword());

// 3. Save via repository
$savedUser = $repo->save($user);

// 4. Return response
return $this->returnJson(['id' => $savedUser->getId()], 201);
```

### Retrieving a User

```php
// 1. Get ID from request
$id = $request->getData('id');

// 2. Search via repository
$user = $repo->find($id);

// 3. Return response
if (!$user) {
    return $this->returnError(404);
}
return $this->returnJson(['name' => $user->getName()]);
```

### Updating a User

```php
// 1. Retrieve existing user
$user = $repo->find($id);
if (!$user) {
    return $this->returnError(404);
}

// 2. Update properties
//Build your own system

// 3. Save via repository
$repo->save($user);

// 4. Return response
return $this->returnJson(null, 204);
```

### Deleting a User

```php
// 1. Retrieve user
$user = $repo->find($id);
if (!$user) {
    return $this->returnError(404);
}

// 2. Delete via repository
$repo->delete($user);

// 3. Return response
return $this->returnJson(null, 204);
```

---

## Summary

| Concept | Description | Example |
|---------|-------------|---------|
| **Entity** | Class representing a business object | `User`, `Product` |
| **Repository** | Class managing data persistence | `UserRepository`, `ProductRepository` |
| **Attributes** | Decorators to control behavior | `#[NotStored]`, `#[Nullable]` |
| **Hydrator** | Fill entity from array | `Hydrator::hydrate($user, $data)` |
| **CRUD** | Create, Read, Update, Delete | `save()`, `find()`, `delete()` |

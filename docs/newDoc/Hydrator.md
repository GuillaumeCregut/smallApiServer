# Hydrator Documentation

## Overview

The `Hydrator` class is a utility that populates entity objects with data from associative arrays. It implements the **Hydrator Design Pattern**, which bridges the gap between raw data (typically from database queries or API responses) and object-oriented entity classes.

## Purpose

The Hydrator serves to:
- **Populate entities** with data from arrays
- **Automatically map properties** from array keys to setter methods
- **Maintain separation of concerns** between data layers and entity logic
- **Provide a clean interface** for data object construction
- **Support fluent setter chains** through method chaining

## Class Structure

### Location

```
App\Kernel\Connector\Hydrator
```

### Dependencies

- `App\Kernel\Interfaces\Databases\EntityInterface` - Required interface for all entities

### Method Signature

```php
public static function hydrate(EntityInterface $entity, array $values): EntityInterface
```

## Basic Usage

### Simple Hydration

```php
<?php

use App\Kernel\Connector\Hydrator;

// Prepare data
$values = [
    'name' => 'Doe',
    'firstname' => 'John',
    'age' => 30
];

// Create entity instance
$entity = new User();

// Hydrate the entity with data
$entity = Hydrator::hydrate($entity, $values);

// Entity is now populated
echo $entity->getName();        // Output: Doe
echo $entity->getFirstname();   // Output: John
echo $entity->getAge();         // Output: 30
```

## How It Works

The Hydrator uses a simple but effective algorithm:

1. **Iterates through the array** - Each key-value pair in the input array
2. **Constructs setter method names** - Converts key `'firstname'` to setter `'setFirstname'`
3. **Checks method existence** - Uses `method_exists()` to verify the entity has the setter
4. **Calls setter methods** - If the method exists, invokes it with the value
5. **Skips unknown properties** - Silently ignores array keys without corresponding setters
6. **Returns the entity** - Returns the hydrated entity for chaining

### Conversion Algorithm

The key-to-method-name conversion follows this pattern:

```
Array key → Method name
'name' → 'setName'
'firstname' → 'setFirstname'
'age' → 'setAge'
'email' → 'setEmail'
'user_id' → 'setUser_id'
```

The pattern is: `'set' + ucfirst($key)`

This means:
- The first letter of the key is capitalized
- The word "set" is prepended
- The rest of the key remains unchanged

## Entity Requirements

### EntityInterface

All entities must implement `EntityInterface`:

```php
<?php

namespace App\Kernel\Interfaces\Databases;

interface EntityInterface
{
    public function getId(): int;
}
```

### Entity Implementation Example

```php
<?php

use App\Kernel\Interfaces\Databases\EntityInterface;

class User implements EntityInterface
{
    private ?string $name = null;
    private ?string $firstname = null;
    private ?int $age = null;

    public function getId(): int
    {
        return 1;
    }

    // Getter for name
    public function getName(): ?string
    {
        return $this->name;
    }

    // Setter for name - required for hydration
    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    // Getter for firstname
    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    // Setter for firstname - required for hydration
    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    // Getter for age
    public function getAge(): ?int
    {
        return $this->age;
    }

    // Setter for age - required for hydration
    public function setAge(?int $age): self
    {
        $this->age = $age;
        return $this;
    }
}
```

## Key Features

### 1. Property Mapping

The hydrator automatically converts array keys to setter methods:

```php
$data = [
    'name' => 'Smith',
    'email' => 'john@example.com',
    'phone' => '123-456-7890'
];

$user = Hydrator::hydrate(new User(), $data);
// Calls: setName(), setEmail(), setPhone()
```

### 2. Graceful Handling of Extra Properties

Extra array keys that don't have corresponding setters are silently ignored:

```php
$data = [
    'name' => 'Doe',
    'firstname' => 'John',
    'age' => 30,
    'mail' => 'john.doe@example.com'  // No setMail() method
];

$entity = Hydrator::hydrate(new User(), $data);
// The 'mail' key is ignored - no error thrown
// Only name, firstname, and age are hydrated
```

This is a safety feature - unexpected data doesn't break the hydration process.

### 3. Method Chaining Support

If your setters return `$this`, you can chain calls naturally:

```php
public function setName(?string $name): self
{
    $this->name = $name;
    return $this;  // Enable chaining
}
```

### 4. Type Flexibility

The hydrator works with any array values and setter parameters:

```php
$data = [
    'name' => 'Doe',           // string
    'age' => 30,               // int
    'active' => true,          // bool
    'roles' => ['admin'],      // array
    'metadata' => null         // null
];

$entity = Hydrator::hydrate(new User(), $data);
// All types are passed as-is to the setters
```

## Common Use Cases

### 1. Populating Entities from Database Results

```php
<?php

use App\Kernel\Connector\Hydrator;
use App\Models\User;

// Get data from database query
   $result = $connector->fetchQuery($sql,[1]);
  // Returns array: ['name' => 'Doe', 'firstname' => 'John', ...]

// Hydrate entity
$user = Hydrator::hydrate(new User(), $result);
```

### 2. Creating Entities from API Requests

```php
<?php

use App\Kernel\Connector\Hydrator;
use App\Models\Product;

// Receive JSON from API
$jsonData = $myApi->getJsonBody();
// $jsonData: ['name' => 'Laptop', 'price' => 999.99, 'stock' => 10]

// Hydrate entity
$product = Hydrator::hydrate(new Product(), $jsonData);
```

### 3. Bulk Entity Creation

```php
<?php

use App\Kernel\Connector\Hydrator;
use App\Models\Order;

// Get multiple records from database
$results = $connector->fetchQuery('orders',[]);

// Hydrate multiple entities
$orders = array_map(function($row) {
    return Hydrator::hydrate(new Order(), $row);
}, $results);
```

### 4. Form Data Processing

```php
<?php

use App\Kernel\Connector\Hydrator;
use App\Models\User;

// Get form data from request
$formData = $request->getAllDatas();

// Create and hydrate entity
$user = Hydrator::hydrate(new User(), $formData);
```

## Method Reference

### `hydrate(EntityInterface $entity, array $values): EntityInterface`

**Parameters:**
- `entity` (EntityInterface): The entity object to populate
- `values` (array): Associative array of key-value pairs

**Returns:** EntityInterface - The hydrated entity (same object passed in)

**Behavior:**
- For each key in `$values`, constructs a setter method name
- Calls the setter if it exists on the entity
- Silently skips keys without corresponding setters
- Returns the modified entity object
- Does not throw exceptions for missing setters

## Testing

The Hydrator is tested through the `HydratorTest` class with comprehensive test cases:

### Test Case 1: Basic Hydration

```php
public function testHydratorEntityOK(): void
{
    $values = [
        'name' => 'Doe',
        'firstname' => 'John',
        'age' => 30
    ];

    $entity = Hydrator::hydrate(new Entity(), $values);

    $this->assertInstanceOf(Entity::class, $entity);
    $this->assertEquals('John', $entity->getFirstname());
    $this->assertEquals('Doe', $entity->getName());
    $this->assertEquals(30, $entity->getAge());
}
```

**What it tests:** Basic functionality with matching properties

### Test Case 2: Extra Properties

```php
public function testHydratorEntityWithMore(): void
{
    $values = [
        'name' => 'Doe',
        'firstname' => 'John',
        'age' => 30,
        'mail' => 'john.doe@example.com'  // Extra, unexpected property
    ];

    $entity = Hydrator::hydrate(new Entity(), $values);

    $this->assertInstanceOf(Entity::class, $entity);
    $this->assertEquals('John', $entity->getFirstname());
    $this->assertEquals('Doe', $entity->getName());
    $this->assertEquals(30, $entity->getAge());
}
```

**What it tests:** Handling of extra array keys that don't have setters

## Advanced Examples

### Example 1: Creating User from Database

```php
<?php

namespace App\Models;

use App\Kernel\Connector\Hydrator;
use App\Kernel\Interfaces\Databases\EntityInterface;

class User implements EntityInterface
{
    private ?int $id = null;
    private ?string $username = null;
    private ?string $email = null;
    private ?string $password = null;
    private ?string $role = null;

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;
        return $this;
    }
}

// Usage
use App\Kernel\Connector\QueryBuilder;

$qb = new QueryBuilder('users');
$qb->select(['id', 'username', 'email', 'password', 'role'])
   ->where('id', '=', 5);

// Get one row
$userData = $connector->fetchQuery($qb->toSQL, $qb->getParams());

// Hydrate the entity
$user = Hydrator::hydrate(new User(), $userData);

// Use the entity
echo $user->getUsername();  // Output: john_doe
```

### Example 2: Processing Form Submission

```php
<?php

namespace App\Controllers;

use App\Kernel\Connector\Hydrator;
use App\Models\Product;
use App\Kernel\AbstractResponse;
use App\Kernel\Request;

class ProductController extends AbstractController
{
    public function store(): AbstractResponse
    {
        $request = Request::getRequestInstance();
        
        // Get form data
        $formData = $request->getAllDatas();
        
        // Create and hydrate product
        $product = Hydrator::hydrate(new Product(), $formData);
        
        // Validate (optional)
        if (!$this->validateProduct($product)) {
            return new JsonResponse(['error' => 'Invalid product'], 400);
        }
        
        // Save to database
        $this->productRepository->save($product);
        
        return new JsonResponse(['success' => true], 201);
    }

    private function validateProduct(Product $product): bool
    {
        return $product->getName() !== null 
            && $product->getPrice() > 0;
    }
}
```

### Example 3: Bulk Data Import

```php
<?php

use App\Kernel\Connector\Hydrator;
use App\Models\Order;

class OrderImporter
{
    public function importFromCSV(string $filePath): array
    {
        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file);
        
        $orders = [];
        
        while ($row = fgetcsv($file)) {
            // Convert CSV row to associative array
            $data = array_combine($headers, $row);
            
            // Hydrate entity
            $order = Hydrator::hydrate(new Order(), $data);
            
            $orders[] = $order;
        }
        
        fclose($file);
        return $orders;
    }
}
```

### Example 4: Data Transformation

```php
<?php

use App\Kernel\Connector\Hydrator;
use App\Models\User;

class UserFactory
{
    public function createFromExternalAPI(array $externalData): User
    {
        // Transform external API format to entity format
        $mappedData = [
            'name' => $externalData['last_name'],
            'firstname' => $externalData['first_name'],
            'email' => $externalData['email_address'],
            'age' => (int)$externalData['age']
        ];
        
        return Hydrator::hydrate(new User(), $mappedData);
    }
}
```

## Edge Cases and Considerations

### Case 1: Null Values

Hydrator passes null values directly to setters:

```php
$data = ['name' => null, 'age' => null];
$entity = Hydrator::hydrate(new User(), $data);
// setName(null) and setAge(null) are called
```

### Case 2: Type Mismatches

The hydrator doesn't perform type coercion - it passes values as-is:

```php
$data = ['age' => '30'];  // string instead of int
$entity = Hydrator::hydrate(new User(), $data);
// setAge('30') is called with string, not int
// Type checking depends on your setter implementation
```

To handle type conversion, use setters that coerce types:

```php
public function setAge($age): self
{
    $this->age = is_numeric($age) ? (int)$age : null;
    return $this;
}
```

### Case 3: Case Sensitivity

Array keys are case-sensitive. `'Name'` and `'name'` are different:

```php
$data = ['Name' => 'Doe'];  // Capital N
$entity = Hydrator::hydrate(new User(), $data);
// Looks for setName() - NOT found!
// Looks for setName() - capital N creates setName() method name

// This won't work as expected
```

Always use lowercase array keys to match typical database column naming conventions.

### Case 4: Complex Objects

Hydrator can't automatically handle complex nested objects:

```php
$data = [
    'name' => 'Doe',
    'address' => ['street' => 'Main', 'city' => 'NYC']  // Nested array
];

$entity = Hydrator::hydrate(new User(), $data);
// setAddress(['street' => 'Main', 'city' => 'NYC']) - array passed as-is
// Your setter must handle the array appropriately
```

## Best Practices

1. **Always implement EntityInterface** - Required by the hydrator
2. **Use type hints in setters** - For better type safety and IDE support
3. **Return `$this` from setters** - Enables method chaining
4. **Use lowercase property names** - Convention matching database columns
5. **Validate after hydration** - Don't assume data is valid
6. **Use specific getter names** - Consistent with setter patterns
7. **Handle type conversion in setters** - Not the hydrator's responsibility
8. **Document expected properties** - Help developers know what data to provide
9. **Test hydration thoroughly** - Ensure all properties map correctly
10. **Consider using factories** - Wrap hydration for complex initialization

## Performance Considerations

- **Lightweight**: The hydrator performs minimal operations
- **Efficient**: Uses `method_exists()` which is fast
- **No reflection overhead**: Direct method calls, not reflection
- **Suitable for bulk operations**: Can hydrate thousands of entities

## Troubleshooting

### Issue: Properties not being set

**Cause**: No corresponding setter method exists

**Solution**: Add the required setter method to your entity

```php
// Entity must have setPropertyName() method
public function setName($name): self
{
    $this->name = $name;
    return $this;
}
```

### Issue: Accessing undefined property

**Cause**: Property was never hydrated

**Solution**: Ensure the array key exists and matches the setter name

```php
// Array key: 'name' → Setter method: 'setName'
$data = ['name' => 'Doe'];
```

### Issue: Type warnings in IDE

**Cause**: Missing setter method or type hints

**Solution**: Properly type your setters

```php
public function setAge(?int $age): self
{
    $this->age = $age;
    return $this;
}
```

## Summary

The Hydrator is a simple yet powerful utility that:
- Maps array data to entity objects automatically
- Follows consistent naming conventions (camelCase)
- Gracefully handles unexpected or extra data
- Works seamlessly with ORM and query builder patterns
- Supports method chaining through proper setter design
- Forms a key part of the data access layer

By understanding and properly using the Hydrator class, you can create clean, maintainable data models and reduce boilerplate code in your application.

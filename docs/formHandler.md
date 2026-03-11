# Form Handler Documentation

## Overview

The `FormHandler` class provides a comprehensive form data processing pipeline that automatically **sanitizes**, **casts**, and **validates** incoming form data using PHP attributes (decorators) on entity properties. It ensures data safety and correctness before entities are hydrated or persisted.

**Key Features:**
- **Three-Stage Pipeline**: Sanitization → Casting → Validation
- **Attribute-Based Validation**: Use PHP 8 attributes to define validation rules
- **Type Safety**: Automatic type casting based on entity property types
- **Security**: Built-in sanitization against XSS and injection attacks
- **File Validation**: Support for file uploads with size and MIME type checks
- **Error Collection**: Detailed error messages for failed validations
- **Optional Fields**: Support for optional properties with `#[Optional]`

---

## Architecture

The `FormHandler` coordinates three specialized components in a linear pipeline:

```
Raw Request Data (POST/JSON/Files)
        ↓
   DataSanitizer
        ↓
   DataCaster
        ↓
   DataValidator
        ↓
Result: {valid, values, errors}
```

### 1. DataSanitizer

Cleans input strings to prevent security issues:
- **Trim whitespace** from beginning and end
- **Strip HTML tags** (prevents injected scripts)
- **Escape special characters** using `htmlspecialchars()`
- **Non-string pass-through**: Arrays, numbers, booleans unchanged
- **Recursive processing**: Handles nested arrays

**Processing Order:**
1. Trim spaces: `"  John  "` → `"John"`
2. Strip tags: `"<b>John</b>"` → `"John"`
3. Escape HTML: `"John & Doe"` → `"John &amp; Doe"`

**Example:**
```php
$raw = ['name' => '  <b>John & Co</b>  '];
$sanitized = DataSanitizer::sanitize($raw);
// Result: ['name' => 'John &amp; Co']
```

### 2. DataCaster

Converts values to their declared entity property types:
- **Detects** property type from entity reflection
- **Casts**: `int`, `float`, `bool`, `string`
- **Preserves** unknown types and null values
- **Smart booleans**: Uses `filter_var(FILTER_VALIDATE_BOOLEAN)`

**Type Mappings:**
| Declared Type | Raw Value | Result |
|--|--|--|
| `int` | `'42'` | `42` |
| `float` | `'3.14'` | `3.14` |
| `bool` | `'1'`, `'true'`, `1` | `true` |
| `string` | `42` | `'42'` |
| Unknown | Any | Unchanged |

**Example:**
```php
class User {
    private int $age;        // int type
    private string $email;   // string type
}

$sanitized = ['age' => '25', 'email' => 'john@example.com'];
$casted = DataCaster::cast(User::class, $sanitized);
// Result: ['age' => 25, 'email' => 'john@example.com']
//         age is now int, not string
```

### 3. DataValidator

Validates casted values using PHP 8 attributes on entity properties:
- **Reads** attributes from entity properties
- **Checks type compatibility** for each validator
- **Accumulates** all errors (first error stops entire validation)
- **Passes null** through (use `#[NotNull]` to reject)
- **Merges files** array with values array
- **Supports multiple** attributes per property

**Validation Flow:**
1. Check property has value (unless `#[Optional]`)
2. Check each attribute's type requirements
3. Execute validation check
4. Return first error or true if all pass

---

## Core Concepts

### Entities

**FormHandler** works with any class implementing `EntityInterface`:

```php
use App\Kernel\Connector\Interfaces\EntityInterface;
use App\Kernel\Form\Validator\Assert\NotBlank;
use App\Kernel\Form\Validator\Assert\Email;
use App\Kernel\Form\Validator\Assert\Min;
use App\Kernel\Form\Validator\Assert\Optional;

class User implements EntityInterface
{
    #[NotBlank]
    #[Email]
    private string $email;

    #[NotBlank]
    private string $password;

    #[Min(18)]
    private int $age;

    #[Optional]
    #[NotBlank]
    private ?string $bio;

    // Required EntityInterface methods
    private int $id;
    
    public function getId(): int { return $this->id; }
    public function setId(int $id): self { $this->id = $id; return $this; }
    public static function getRepository(): string { return ''; }
}
```

### Attributes (Validators)

Attributes are decorators that define validation rules on entity properties:

```php
#[NotBlank]          // Property cannot be empty
#[Email]             // Must be valid email
#[Min(18)]           // Number must be >= 18
#[Optional]          // Field is optional
#[File(2097152)]     // File upload with max 2MB
```

**Key Rules:**
- Multiple attributes per property are supported
- `#[Optional]` must come first (it skips the property if missing)
- `null` values pass all validators except `#[NotNull]`
- File attributes work with `FileUpload` objects

---

## FormHandler Usage

### Basic Usage

```php
use App\Kernel\Form\FormHandler;

// Get form data from request
$rawValues = [
    'email'    => '  john@example.com  ',
    'password' => 'secret123',
    'age'      => '25',
];

// Process through FormHandler
$result = FormHandler::handle(User::class, $rawValues);

// Check results
if ($result['valid']) {
    // Sanitized, casted, and validated data ready to use
    $user = new User();
    Hydrator::hydrate($user, $result['values']);
    $repo->save($user);
} else {
    // Return validation errors to client
    return $this->returnJson(['errors' => $result['errors']], 422);
}
```

### Returned Array Structure

```php
[
    'valid'  => true|false,              // Boolean validation result
    'values' => [                        // Sanitized and casted values
        'email' => 'john@example.com',   // Ready for Hydrator
        'password' => 'secret123',
        'age' => 25,                     // Already casted to int
    ],
    'errors' => [                        // Only if validation fails
        'field_name' => [
            'ValidatorClass' => 'Error message'
        ]
    ]
]
```

### With File Uploads

```php
// File is passed separately
$files = [];
if ($request->getFile('avatar')) {
    $files['avatar'] = $request->getFile('avatar')[0];  // FileUpload instance
}

$result = FormHandler::handle(User::class, $values, $files);
```

### In Controller

```php
public function register(): ResponseInterface
{
    $data = $this->request->getAllDatas();
    
    $result = FormHandler::handle(User::class, $data);
    
    if (!$result['valid']) {
        return $this->returnError(422, $result['errors']);
    }
    
    // Data is sanitized, casted, and validated
    $user = Hydrator::hydrate(new User(), $result['values']);
    $savedUser = $this->userRepo->save($user);
    
    return $this->returnJson(['id' => $savedUser->getId()], 201);
}
```

---

## Validators (Attributes)

All validators are PHP 8 attributes that implement `ValidatorInterface`. They define validation rules on entity properties.

### Common Rules

#### Type Checking
Validators check if the value is of the correct type:
- **String validators**: `NotBlank`, `Email`, `Url`
- **Numeric validators**: `Min`, `Max`, `Positive`, `Negative`
- **Type validators**: `NotNull`, `Equals`
- **File validators**: `File`, `Multiplefiles`

**Null Handling:**
- All validators **pass null** by default (optional values)
- Use `#[NotNull]` to reject null
- Use `#[Optional]` to make entire field optional

### String Validators

#### NotBlank
```php
#[NotBlank]
private string $name;

// Fails: '', '   ' (whitespace)
// Passes: 'John', ' John ' (sanitizer will trim)
```

#### Email
```php
#[Email]
private string $email;

// Fails: 'invalid', 'user@', '@domain.com'
// Passes: 'user@example.com'
```

#### Url
```php
#[Url]
private string $website;

// Fails: 'not-a-url', 'example.com' (no protocol)
// Passes: 'https://example.com'
```

#### Equals
```php
#[Equals('admin')]
private string $role;

// Fails: 'user', 'superadmin'
// Passes: 'admin'
```

### Numeric Validators

All numeric validators **pass null** values by default (use `#[NotNull]` if needed).

#### Min / MoreOrEquals
```php
#[Min(0)]
private int $age;

#[Min(18)]
private int $minimumAge;

// Same behavior — validates value >= expected
```

#### Max / LessOrEquals
```php
#[Max(120)]
private int $age;

// Validates value <= expected
```

#### MoreThan
```php
#[MoreThan(0)]
private int $count;

// Validates value > expected
```

#### LessThan
```php
#[LessThan(100)]
private int $score;

// Validates value < expected
```

#### Positive
```php
#[Positive]
private int $count;

// Validates value > 0
// Equivalent to #[MoreThan(0)]
```

#### Negative
```php
#[Negative]
private int $balance;

// Validates value < 0
```

#### PositiveOrZero
```php
#[PositiveOrZero]
private int $count;

// Validates value >= 0
```

#### NegativeOrZero
```php
#[NegativeOrZero]
private int $balance;

// Validates value <= 0
```

### Special Validators

#### Optional
```php
#[Optional]
#[NotBlank]
private ?string $bio;

// Field is not required
// If present, must be not blank
// Must come first in attributes list
```

#### NotNull
```php
#[NotNull]
private string $email;

// Explicitly rejects null
// Usually implicit, but useful with `?string` types
```

### File Validators

#### File (Single Upload)
```php
#[File(
    maxSize: 2097152,              // 2MB in bytes
    allowedMimeTypes: ['image/jpeg', 'image/png'],
    allowedExtensions: ['jpg', 'png']
)]
private mixed $avatar;

// Validates FileUpload instance
// Checks size, MIME type, extension
// Pass empty array to allow all
```

#### Multiplefiles (Multiple Uploads)
```php
#[Multiplefiles(
    maxSize: 5242880,              // 5MB
    allowedMimeTypes: ['application/pdf'],
    allowedExtensions: ['pdf']
)]
private array $documents;

// Validates array of FileUpload instances
```

### Custom Error Messages

Override default error messages:

```php
#[NotBlank(errorMessage: 'Name is required')]
private string $name;

#[Email(errorMessage: 'Please enter a valid email address')]
private string $email;

#[Min(18, errorMessage: 'You must be 18 or older')]
private int $age;
```

---

## Practical Examples

### User Registration

```php
use App\Kernel\Form\Validator\Assert\NotBlank;
use App\Kernel\Form\Validator\Assert\Email;
use App\Kernel\Form\Validator\Assert\Min;

class User implements EntityInterface
{
    #[NotBlank]
    private string $name;

    #[NotBlank]
    #[Email]
    private string $email;

    #[NotBlank]
    private string $password;

    #[Min(18)]
    private int $age;

    // Required methods...
    private int $id;
    public function getId(): int { return $this->id; }
    public function setId(int $id): self { $this->id = $id; return $this; }
    public static function getRepository(): string { return UserRepository::class; }
}

// In Controller
public function register(): ResponseInterface
{
    $data = $this->request->getAllDatas();
    $result = FormHandler::handle(User::class, $data);

    if (!$result['valid']) {
        return $this->returnJson(['errors' => $result['errors']], 422);
    }

    $user = Hydrator::hydrate(new User(), $result['values']);
    $user->setNewPassword($user->getPassword());
    $savedUser = $this->userRepo->save($user);

    return $this->returnJson(['id' => $savedUser->getId()], 201);
}
```

### Product Upload with Optional Fields

```php
use App\Kernel\Form\Validator\Assert\NotBlank;
use App\Kernel\Form\Validator\Assert\Min;
use App\Kernel\Form\Validator\Assert\File;
use App\Kernel\Form\Validator\Assert\Optional;
use App\Kernel\Form\Validator\Assert\Positive;

class Product implements EntityInterface
{
    #[NotBlank]
    private string $title;

    #[NotBlank]
    private string $description;

    #[Positive]
    private float $price;

    #[Optional]
    #[NotBlank]
    private ?string $sku;

    #[Optional]
    #[File(
        maxSize: 1048576,                  // 1MB
        allowedMimeTypes: ['image/jpeg', 'image/png']
    )]
    private mixed $image;

    // Required methods...
}

// In Controller
public function create(): ResponseInterface
{
    $data = $this->request->getAllDatas();
    
    $files = [];
    if ($request->getFile('image')) {
        $files['image'] = $request->getFile('image')[0];
    }

    $result = FormHandler::handle(Product::class, $data, $files);

    if (!$result['valid']) {
        return $this->returnJson(['errors' => $result['errors']], 422);
    }

    $product = Hydrator::hydrate(new Product(), $result['values']);
    $this->productRepo->save($product);

    return $this->returnJson(['success' => true], 201);
}
```

### Blog Post with Author Bio

```php
class BlogPost implements EntityInterface
{
    #[NotBlank]
    private string $title;

    #[NotBlank]
    private string $content;

    #[Min(1)]
    private int $authorId;

    #[Optional]
    #[NotBlank]
    private ?string $excerpt;

    #[Optional]
    #[Url]
    private ?string $relatedLink;

    // Required methods...
}

// In Controller
public function create(): ResponseInterface
{
    $data = $this->request->getAllDatas();
    $result = FormHandler::handle(BlogPost::class, $data);

    if (!$result['valid']) {
        return $this->returnJson(['errors' => $result['errors']], 422);
    }

    // Data is clean and ready
    $post = Hydrator::hydrate(new BlogPost(), $result['values']);
    $this->blogRepo->save($post);

    return $this->returnJson(['id' => $post->getId()], 201);
}
```

---

## Pipeline Demonstration

### Complete Example

```php
$rawInput = [
    'name'  => '  <script>alert(1)</script>John  ',
    'age'   => '25',               // string from form
    'email' => '  john@example.com  ',
];

// STEP 1: SANITIZATION
// Triggers for strings only
$sanitized = [
    'name'  => 'alert(1)John',     // trimmed, tags stripped
    'age'   => '25',               // unchanged (not a string)
    'email' => 'john@example.com',  // trimmed
];

// STEP 2: CASTING (based on entity property types)
$casted = [
    'name'  => 'alert(1)John',     // string → string unchanged
    'age'   => 25,                 // string '25' → int 25
    'email' => 'john@example.com',  // string → string unchanged
];

// STEP 3: VALIDATION (using attributes)
// Checks: #[NotBlank], #[Email], #[Min(18)]
// - name: not blank ✓
// - age: >= 18 ✓
// - email: valid email ✓

$result = [
    'valid'  => true,
    'values' => $casted,           // ready for Hydrator
    'errors' => []
];
```

### Error Scenario

```php
$rawInput = [
    'name'  => '   ',              // only spaces
    'age'   => '15',               // below minimum
    'email' => 'invalid-email',    // not an email
];

// After sanitization, casting
$toValidate = [
    'name'  => '',                 // trimmed to empty
    'age'   => 15,                 // casted to int
    'email' => 'invalid-email',
];

// Validation fails on first error:
// name: NotBlank fails → return false

$result = [
    'valid'  => false,
    'values' => $toValidate,       // possibly incomplete
    'errors' => [
        'name' => [
            'NotBlank' => 'property name must not be blank'
        ]
    ]
];
```

---

## Error Handling

### Error Structure

When validation fails, errors include:

```php
$result['errors'] = [
    'field_name' => [
        'ValidatorClassName' => 'Error message with %s replaced'
    ]
];

// Example:
[
    'email' => [
        'Email' => 'Value email must not be blank'
    ],
    'age' => [
        'Min' => 'property age must be more or equals than 18'
    ]
];
```

### Error Collection

```php
public function validate(): ResponseInterface
{
    $result = FormHandler::handle(User::class, $data);

    if ($result['valid'] === false) {
        // Return errors to JavaScript
        return $this->returnJson(
            ['errors' => $result['errors']],
            422  // Unprocessable Entity
        );
    }

    // Process valid data
}
```

### Displaying Errors

```javascript
// Frontend JavaScript
fetch('/register', { method: 'POST', body: form })
    .then(r => r.json())
    .then(data => {
        if (data.errors) {
            // Display field errors
            Object.entries(data.errors).forEach(([field, validators]) => {
                Object.entries(validators).forEach(([validator, message]) => {
                    displayError(field, message);
                });
            });
        }
    });
```

---

## Best Practices

### ✅ Do's

```php
// 1. Use FormHandler for all external input
public function create(): ResponseInterface
{
    $result = FormHandler::handle(User::class, $this->request->getAllDatas());
    if (!$result['valid']) {
        return $this->returnError(422, $result['errors']);
    }
    // Process $result['values']
}

// 2. Mark optional fields with #[Optional]
#[Optional]
#[NotBlank]
private ?string $bio;

// 3. Use specific validators
#[Email]               // Not just NotBlank
#[Url]                 // For URLs
#[Min(18)]            // For numeric constraints

// 4. Chain multiple validators for complex rules
#[NotBlank]
#[Email]
#[Equals('admin@example.com')]
private string $adminEmail;

// 5. Provide custom error messages
#[Min(18, errorMessage: 'Must be 18 or older')]
private int $age;

// 6. Use type hints for automatic casting
private int $age;      // Will be cast from string '25' → int 25
private float $price;  // Will be cast from '9.99' → float 9.99
private bool $active;  // Will be cast from 'true' → bool true
```

### ❌ Don'ts

```php
// 1. Don't skip FormHandler for untrusted input
$user->setEmail($_POST['email']);  // ✗ No sanitization/validation

// 2. Don't trust client-side validation alone
// Always validate on server with FormHandler

// 3. Don't over-validate (use #[Optional] appropriately)
// Every field should have a reason for being required

// 4. Don't use wrong validator types
#[Min(18)]           // For numeric
// Not for strings! Use #[NotBlank] + #[Min(...)] separately

// 5. Don't forget to hydrate after validation
$result = FormHandler::handle(User::class, $data);
$user = new User();
Hydrator::hydrate($user, $result['values']);  // Essential step

// 6. Don't rely on null handling for optional
#[Min(18)]
private ?int $age;    // Better: use #[Optional]

// Better:
#[Optional]
#[Min(18)]
private ?int $age;
```

### Validation Order

1. **Sanitize first** (reduces bad data early)
2. **Cast second** (ensures correct types)
3. **Validate last** (validates already-cleaned data)
4. **Handle errors** (return 422 with error details)
5. **Hydrate entity** (use cleaned, valid data)
6. **Persist** (save to database)

---

## Security Considerations

### XSS Prevention

```php
// Input: '<script>alert(1)</script>'
// After sanitization: ''
// Tags are stripped, safe for HTML output

// Input: 'John & "Admin"'
// After sanitization: 'John &amp; "Admin"'
// HTML special chars escaped, safe for output
```

### Type Safety

```php
// Input: '25' (string from form)
// Declared type: int
// After casting: 25 (integer)
// Type mismatch prevented in entity

// Reduces: SQL injection, type confusion attacks
```

### Null Handling

```php
// All validators pass null by default
// This allows optional fields

// To reject null explicitly:
#[NotNull]
#[NotBlank]
private string $email;

// Now: null → fails (rejected)
```

---

## Summary

| Component | Purpose |
|-----------|---------|
| **FormHandler** | Orchestrates sanitize → cast → validate pipeline |
| **DataSanitizer** | Cleans strings (trim, strip tags, escape HTML) |
| **DataCaster** | Converts values to entity property types |
| **DataValidator** | Validates using PHP 8 attributes |
| **Attributes** | Define validation rules on properties |
| **Result Array** | Contains {valid, values, errors} |

| Feature | How |
|---------|-----|
| **Prevent XSS** | Sanitize strips tags and escapes HTML |
| **Ensure Types** | Cast converts form strings to correct types |
| **Validate Data** | Attributes define business rules |
| **Handle Errors** | Return 422 with detailed error map |
| **Optional Fields** | Use `#[Optional]` attribute |
| **File Uploads** | Pass files array + File validator |

---

## Related Documentation

- [Request Documentation](./request.md) - Accessing form data from HTTP requests
- [Hydrator Documentation](./hydrator.md) - Converting arrays to entity instances
- [Entity Documentation](./repository.md#entities) - Entity structure and requirements
- [Validator Interface](./formHandler.md#validators) - Creating custom validators

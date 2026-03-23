# Repositories, Entities, and Entity Management

## Overview

The smallAPIServer framework provides **two complementary patterns** for managing entities: the traditional **Repository pattern** and the modern **EntityManager pattern** (Unit of Work). Choose one approach for your application based on your needs.

### Two Approaches: Repository vs EntityManager

| Feature | Repository | EntityManager |
|---------|-----------|---------------|
| **Usage** | Direct save/find on demand | Track entities, flush all at once |
| **Transactions** | Per operation | Single flush (all-or-nothing) |
| **Identity Map** | None | Yes (one object per ID) |
| **Relationships** | Manual sync | Auto-synced |
| **Learning Curve** | Simple | Moderate |
| **Best For** | CRUD controllers | Complex business logic |

**⚠️ WARNING**: Use EITHER Repository OR EntityManager, but NOT BOTH in the same request. Mixing them can cause data consistency issues and duplicate operations.

---

## Entities

### Concept

An Entity is a class that represents a business object with its data and properties. It implements the `EntityInterface` and typically inherits from `AbstractEntity`. They must be final classes.

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

final class User extends AbstractEntity implements UserEntityInterface
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

## Entity Manager (Unit of Work Pattern)

The `EntityManager` implements the **Unit of Work pattern**, managing entity persistence lifecycle within a request.

### Concept

Instead of saving individual entities immediately, the EntityManager:
1. **Tracks** entity changes (new, modified, deleted)
2. **Manages Identity Map** - ensures one PHP object per database record
3. **Orders** dependencies automatically (e.g., ManyToOne targets before owners)
4. **Flushes** all changes in a single transaction

### Initialization

```php
use App\Kernel\Connector\Management\EntityManager;
use App\Kernel\Connector\Management\IdentityMap;

// Simple initialization
$em = EntityManager::getInstance();

// Or with custom IdentityMap
$em = EntityManager::getInstance(new IdentityMap());
```

### Core Methods

#### persist(EntityInterface $entity): void

Mark an entity for persistence (insert or update).

```php
$user = new User();
$user->setName('John');

// Entity has no ID yet - will be INSERTed
$em->persist($user);

// After loading from DB or setting manually
$existingUser->setName('Jane');
$em->persist($existingUser);  // Will be UPDATED
```

#### remove(EntityInterface $entity): void

Mark an entity for deletion.

```php
$user = $em->find(User::class, 1);
$em->remove($user);  // Will be DELETED on flush
```

#### flush(): void

Persist all pending changes in a **single transaction**:
- Inserts new entities (ordered by ManyToOne dependencies)
- Updates modified entities
- Deletes removed entities
- Rolls back entire transaction on any error

```php
$author = new Author();
$author->setName('John');
$em->persist($author);

$post = new Post();
$post->setTitle('Hello World');
$post->setAuthor($author);  // ManyToOne
$em->persist($post);

$em->flush();  // Transaction ensures both are saved or both rolled back
```

#### find(string $class, int $id): ?EntityInterface

Retrieve an entity from the identity map or database.

```php
$user = $em->find(User::class, 1);

// Same object instance on subsequent calls (identity map)
$sameUser = $em->find(User::class, 1);
assert($user === $sameUser);  // ✓ Same object
```

#### findBy(string $class, array $criteria): array

Find multiple entities by criteria.

```php
$admins = $em->findBy(User::class, ['role' => 'admin']);
```

#### findAll(string $class): array

Retrieve all entities of a type.

```php
$allUsers = $em->findAll(User::class);
```

#### detach(EntityInterface $entity): void

Stop tracking an entity (removes from identity map and all queues).

```php
$em->detach($user);  // Entity no longer tracked
$em->persist($user);  // Must re-persist
```

#### clear(): void

Clear all tracked entities and the identity map.

```php
$em->clear();  // Reset everything
```

#### transactional(callable $fn): void

Execute a block of code within a transaction.

```php
$em->transactional(function() use ($em) {
    $user = new User();
    $user->setName('Alice');
    $em->persist($user);
    $em->flush();
});
// If exception thrown, entire transaction is rolled back
```

#### getIdentityMap(): IdentityMapInterface

Access the identity map directly (advanced use only).

```php
$map = $em->getIdentityMap();
```

### Status Checking Methods

#### isManaged(EntityInterface $entity): bool

Check if entity is tracked (new or dirty).

```php
if ($em->isManaged($user)) {
    $em->flush();  // Will persist this user
}
```

#### isNew(EntityInterface $entity): bool

Check if entity is new (will be INSERTed on flush).

```php
$user = new User();
$em->persist($user);

if ($em->isNew($user)) {
    echo "Entity will be inserted";
}
```

### Entity Manager Workflow Example

```php
use App\Kernel\Connector\Management\EntityManager;

class OrderService
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function placeOrder(array $orderData, array $itemsData): void
    {
        try {
            // Create order
            $order = new Order();
            $order->setTotal(0);
            $this->em->persist($order);

            // Add items
            foreach ($itemsData as $itemData) {
                $item = new OrderItem();
                $item->setOrder($order);  // ManyToOne
                $item->setQuantity($itemData['qty']);
                $item->setPrice($itemData['price']);
                $this->em->persist($item);
                
                // Update total
                $order->addOrderItem($item);  // OneToMany auto-sync
            }

            // Single transaction - all or nothing
            $this->em->flush();

        } catch (Exception $e) {
            // Entire order creation rolled back
            throw $e;
        }
    }
}
```

---

## Entity Relationships and Synchronization

### OneToMany and ManyToOne Attributes

Define relationships in your entities:

```php
use App\Kernel\Connector\Attributes\OneToMany;
use App\Kernel\Connector\Attributes\ManyToOne;

final class Author extends AbstractEntity
{
    #[OneToMany(targetEntity: Post::class, mappedBy: 'author', onUpdate: 'restrict', onDelete: 'restrict')]
    private LazyBag $posts;

    public function addPost(Post $post): void
    {
        $this->addToCollection('posts', $post);
    }
}

class Post extends AbstractEntity
{
    #[ManyToOne(targetEntity: Post::class, inversedBy: 'posts')]
    private ?Author $author = null;

    public function setAuthor(?Author $author): self
    {
        $this->syncRelation('author', $author);
        $this->author = $author;
        return $this;
    }
}
```

### Automatic Synchronization

When using `addToCollection()` or `syncRelation()`, relationships are **automatically synchronized** in both directions:

```php
$author = new Author();
$post = new Post();

// This automatically:
// 1. Sets $post->author to $author
// 2. Adds $post to $author->posts
$author->addPost($post);

// Both EntityManager and Repository support this
$em->persist($author);
$em->persist($post);
$em->flush();  // Both relationships saved correctly
```

### ManyToMany Attribute

Define many-to-many relationships using the `#[ManyToMany]` attribute. A many-to-many relationship exists between two entities through a pivot table.

**Attribute Parameters:**

- `targetEntity`: The class of the related entity
- `pivotTable`: The name of the pivot/junction table that stores the relationship
- `ownerColumn`: Local key column for the owning entity in the pivot table
- `targetColumn`: Foreign key column for the target entity in the pivot table
- `inversedBy`: (owner side only) The property name on the target entity that maps back
- `mappedBy`: (inverse side only) The property name on the owning entity that owns the relationship

**Entities with ManyToMany Relationship:**

```php
use App\Kernel\Connector\Attributes\ManyToMany;
use App\Kernel\Connector\Datas\LazyBag;

// Owner side (defines the relationship)
final class CourseEntity extends AbstractEntity
{
    protected string $name;

    #[ManyToMany(
        targetEntity: SchoolEntity::class,
        ownerColumn: 'course_id',
        targetColumn: 'school_id',
        pivotTable: 'courses_schools',
        inversedBy: 'courses'
    )]
    protected LazyBag $schools;

    public function getName(): string 
    { 
        return $this->name; 
    }

    public function setName(string $name): self 
    { 
        $this->name = $name; 
        return $this; 
    }

    public function getSchools(): LazyBag 
    { 
        return $this->schools; 
    }

    public function setSchools(LazyBag $schools): self 
    { 
        $this->schools = $schools; 
        return $this; 
    }

    public function addSchool(SchoolEntity $school): self
    {
        $this->addToManyToMany('schools', $school);
        return $this;
    }

    public function removeSchool(SchoolEntity $school): self
    {
        $this->removeFromManyToMany('schools', $school);
        return $this;
    }
}

// Inverse side (mapped by owner)
final class SchoolEntity extends AbstractEntity
{
    protected string $name;

    #[ManyToMany(
        targetEntity: CourseEntity::class,
        ownerColumn: 'course_id',
        targetColumn: 'school_id',
        pivotTable: 'courses_schools',
        mappedBy: 'schools'
    )]
    protected LazyBag $courses;

    public function getName(): string 
    { 
        return $this->name; 
    }

    public function setName(string $name): self 
    { 
        $this->name = $name; 
        return $this; 
    }

    public function getCourses(): LazyBag 
    { 
        return $this->courses; 
    }

    public function setCourses(LazyBag $courses): self 
    { 
        $this->courses = $courses; 
        return $this; 
    }

    public function addCourse(CourseEntity $course): self
    {
        $this->addToManyToMany('courses', $course);
        return $this;
    }

    public function removeCourse(CourseEntity $course): self
    {
        $this->removeFromManyToMany('courses', $course);
        return $this;
    }
}
```

**Pivot Table Schema:**

The pivot table stores the relationships between the two entities:

```sql
CREATE TABLE courses_schools (
    course_id INT NOT NULL,
    school_id INT NOT NULL,
    PRIMARY KEY (course_id, school_id),
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (school_id) REFERENCES schools(id)
);
```

### ManyToMany Collection Management

Use `addToManyToMany()` and `removeFromManyToMany()` helper methods to manage relationships:

```php
$course = new CourseEntity();
$course->setName('PHP 101');

$school = new SchoolEntity();
$school->setName('Code Academy');

// Add relationship in both directions
$course->addSchool($school);  // $course->schools contains $school
                              // $school->courses contains $course

// Both EntityManager and Repository support this
$em->persist($course);
$em->persist($school);
$em->flush();  // Inserts course, school, and the pivot table entry

// Later, remove the relationship
$course->removeSchool($school);
$em->flush();  // Deletes the pivot table entry
```

**Getting Related Entities:**

```php
$course = $em->find(CourseEntity::class, 1);

// Get all schools for this course
$schools = $course->getSchools();  // Returns LazyBag

// Iterate through schools
foreach ($schools as $school) {
    echo $school->getName();
}

// Access specific school (if you know the index)
$firstSchool = $schools->get(0);
```

**Querying with Relationships:**

```php
// Find a school's courses
$school = $em->find(SchoolEntity::class, 1);
$courses = $school->getCourses();  // LazyBag of CourseEntity

// Direct relationship checking
if ($school->getCourses()->contains($courseToCheck)) {
    echo "Course is taught at this school";
}
```

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

The repository now accepts an optional EntityManager:

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

**Using with EntityManager:**

```php
$em = EntityManager::getInstance();
$repo = new UserRepository($em);  // Pass EntityManager to constructor

$user = $repo->find(1);
// User is now in identity map and tracked by $em
```

**Using directly (traditional Repository approach):**

```php
$repo = new UserRepository();  // No EntityManager

$user = $repo->find(1);
// User retrieved but not tracked
$repo->save($user);  // Direct save
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

## Repository Events

The repository dispatcher emits events at key lifecycle moments for INSERT, UPDATE, DELETE, and SELECT operations. This allows you to hook into data operations and perform custom logic like validation, auditing, or side effects.

### Event Lifecycle

#### INSERT (Create New Entity)

```
1. save(entity) called with entity.id = null
   ↓
2. PrePersistEvent dispatched
   - Entity can be modified by listeners
   ↓
3. SQL INSERT executed
   ↓
4. Entity gets new ID assigned
   ↓
5. PostPersistEvent dispatched
   - Entity has ID available
   - Only dispatched if INSERT succeeds
```

#### UPDATE (Save Existing Entity)

```
1. save(entity) called with entity.id set
   ↓
2. PreUpdateEvent dispatched
   - Entity can be modified by listeners
   ↓
3. SQL UPDATE executed
   ↓
4. PostUpdateEvent dispatched
   - Only dispatched if UPDATE succeeds
```

#### DELETE

```
1. delete(entity) called
   ↓
2. PreRemoveEvent dispatched
   - Entity data still accessible
   ↓
3. SQL DELETE executed
   ↓
4. PostRemoveEvent dispatched
   - Only dispatched if DELETE succeeds
   - Entity data still accessible
```

#### READ (Find)

```
1. find(id), findBy(...), or findAll() called
   ↓
2. SQL SELECT executed
   ↓
3. Entity hydrated from database
   ↓
4. PostFindEvent dispatched for each entity
   - Entity fully loaded with all data
```

### Event Classes

All repository events inherit from `AbstractStoppableEvent` and include the entity being affected.

| Operation | Event Class | When Dispatched | Entity ID |
|-----------|-------------|-----------------|-----------|
| **INSERT** | `PrePersistEvent` | Before INSERT | Not set |
| | `PostPersistEvent` | After INSERT (success only) | **Assigned** |
| **UPDATE** | `PreUpdateEvent` | Before UPDATE | Set |
| | `PostUpdateEvent` | After UPDATE (success only) | Set |
| **DELETE** | `PreRemoveEvent` | Before DELETE | Set |
| | `PostRemoveEvent` | After DELETE (success only) | Set |
| **SELECT** | `PostFindEvent` | After entity hydrated | Set |

### Registering Event Listeners

Events are managed through the `ListenerProvider`:

```php
use App\Kernel\Psr14\Listener\ListenerProvider;
use App\Kernel\Psr14\Events\PrePersistEvent;
use App\Kernel\Interfaces\Psr14\ListenerInterface;

class AuditListener implements ListenerInterface
{
    public function execute(StoppableEventInterface $event): void
    {
        if ($event instanceof PrePersistEvent) {
            // Handle PrePersistEvent
        }
    }
}

ListenerProvider::getInstance()->addListener(
    PrePersistEvent::class,
    new AuditListener()
);
```

### PrePersistEvent (Before INSERT)

Dispatched before a new entity is inserted into the database.

**When:** `save($entity)` called with `$entity->getId() === null`

**Listener Method:**
```php
public function execute(StoppableEventInterface $event): void
{
    if ($event instanceof PrePersistEvent) {
        $entity = $event->getEntity();
        // Entity is a User, Product, etc.
    }
}
```

**Use Cases:**
- Validate entity data before persistence
- Set computed fields (timestamps, defaults)
- Encrypt sensitive data
- Generate slugs or identifiers

**Example:**
```php
class TimestampListener implements ListenerInterface
{
    public function execute(StoppableEventInterface $event): void
    {
        if ($event instanceof PrePersistEvent) {
            $entity = $event->getEntity();
            
            if (method_exists($entity, 'setCreatedAt')) {
                $entity->setCreatedAt(new DateTime());
            }
        }
    }
}
```

**Modifying Entity:**
```php
$listener = new class implements ListenerInterface {
    public function execute(StoppableEventInterface $event): void
    {
        if ($event instanceof PrePersistEvent) {
            $entity = $event->getEntity();
            // Modifications affect what gets saved
            $entity->setStatus('active');
        }
    }
};
```

### PostPersistEvent (After INSERT)

Dispatched after a new entity is successfully inserted into the database.

**When:** `save($entity)` completes INSERT successfully

**Important:** NOT dispatched if INSERT fails (`$result = false`).

**Entity State:**
- ID is now assigned
- Database record created

**Use Cases:**
- Notify external systems (webhooks, queues)
- Trigger related operations
- Log successful creation
- Update search indices

**Example:**
```php
class SearchIndexListener implements ListenerInterface
{
    public function execute(StoppableEventInterface $event): void
    {
        if ($event instanceof PostPersistEvent) {
            $entity = $event->getEntity();
            $id = $entity->getId();  // ID is now available
            
            // Index in search engine
            $this->indexSearch($entity, $id);
        }
    }
}
```

### PreUpdateEvent (Before UPDATE)

Dispatched before an existing entity is updated in the database.

**When:** `save($entity)` called with `$entity->getId() !== null`

**Use Cases:**
- Validate changes before update
- Track audit trail of what changed
- Modify data before persistence
- Prevent certain updates

**Example:**
```php
class AuditChangeListener implements ListenerInterface
{
    public function execute(StoppableEventInterface $event): void
    {
        if ($event instanceof PreUpdateEvent) {
            $entity = $event->getEntity();
            $id = $entity->getId();
            
            // Log what's being changed
            $oldEntity = $this->fetchOriginal($id);
            $this->logChanges($oldEntity, $entity);
        }
    }
}
```

**Modifying Before Update:**
```php
$listener = new class implements ListenerInterface {
    public function execute(StoppableEventInterface $event): void
    {
        if ($event instanceof PreUpdateEvent) {
            $entity = $event->getEntity();
            
            if (method_exists($entity, 'setUpdatedAt')) {
                $entity->setUpdatedAt(new DateTime());
            }
        }
    }
};
```

### PostUpdateEvent (After UPDATE)

Dispatched after an existing entity is successfully updated in the database.

**When:** `save($entity)` completes UPDATE successfully

**Important:** NOT dispatched if UPDATE fails (`$result = false`).

**Use Cases:**
- Notify of entity changes
- Invalidate caches
- Trigger workflows
- Send notifications

**Example:**
```php
class CacheInvalidateListener implements ListenerInterface
{
    public function execute(StoppableEventInterface $event): void
    {
        if ($event instanceof PostUpdateEvent) {
            $entity = $event->getEntity();
            $id = $entity->getId();
            
            // Clear cache for this entity
            $this->cache->delete("entity_{$id}");
        }
    }
}
```

### PreRemoveEvent (Before DELETE)

Dispatched before an entity is deleted from the database.

**When:** `delete($entity)` called

**Entity Data:** Fully accessible

**Use Cases:**
- Log deletion (audit trail)
- Archive data
- Prevent deletion of protected records
- Cascade operations to related entities

**Example:**
```php
class ArchiveListener implements ListenerInterface
{
    public function execute(StoppableEventInterface $event): void
    {
        if ($event instanceof PreRemoveEvent) {
            $entity = $event->getEntity();
            
            // Archive before deletion
            $this->archiveService->archive($entity->getId());
        }
    }
}
```

### PostRemoveEvent (After DELETE)

Dispatched after an entity is successfully deleted from the database.

**When:** `delete($entity)` completes successfully

**Important:** NOT dispatched if DELETE fails (`$result = false`).

**Entity Data:** Still accessible even though deleted from DB

**Use Cases:**
- Log successful deletion
- Notify external systems
- Clean up related resources
- Update search indices

**Example:**
```php
class SearchRemoveListener implements ListenerInterface
{
    public function execute(StoppableEventInterface $event): void
    {
        if ($event instanceof PostRemoveEvent) {
            $entity = $event->getEntity();
            $id = $entity->getId();
            
            // Remove from search index
            $this->searchService->remove($id);
            
            // Log deletion
            $this->auditLog->recordDeletion($entity::class, $id);
        }
    }
}
```

### PostFindEvent (After SELECT)

Dispatched after each entity is hydrated from a SELECT query.

**When:** Entity loaded from database by `find()`, `findBy()`, or `findAll()`

**Entity State:**
- Fully hydrated with all database values
- ID set
- Ready to use

**Frequency:** Dispatched once per entity found
- `find(1)` returns 1 entity → PostFindEvent dispatched 1 time
- `findAll()` returns 5 entities → PostFindEvent dispatched 5 times
- `findBy(['status' => 'active'])` returns 10 entities → PostFindEvent dispatched 10 times

**Use Cases:**
- Decrypt sensitive fields
- Load related data (lazy loading)
- Transform repository data
- Populate computed properties
- Track entity access

**Example:**
```php
class LazyLoadListener implements ListenerInterface
{
    public function execute(StoppableEventInterface $event): void
    {
        if ($event instanceof PostFindEvent) {
            $entity = $event->getEntity();
            
            // If entity has comments relation, load them
            if (method_exists($entity, 'loadComments')) {
                $entity->loadComments();  // Lazy load related
            }
        }
    }
}
```

### Using Event Bag for Listener Communication

Listeners can share data through the event's bag (inherited from `AbstractStoppableEvent`):

```php
// Listener 1: Compute value
ListenerProvider::getInstance()->addListener(
    PrePersistEvent::class,
    new class implements ListenerInterface {
        public function execute(StoppableEventInterface $event): void
        {
            $event->addInBag('price_usd', 100 * 1.1);  // With tax
        }
    }
);

// Listener 2: Use computed value
ListenerProvider::getInstance()->addListener(
    PrePersistEvent::class,
    new class implements ListenerInterface {
        public function execute(StoppableEventInterface $event): void
        {
            if ($event->hasInBag('price_usd')) {
                $price = $event->getFromBag('price_usd');
                $entity = $event->getEntity();
                $entity->setTotal($price);
            }
        }
    }
);
```

### Real-World Example: Complete Audit Trail

```php
class AuditTrailService
{
    public function registerListeners(): void
    {
        ListenerProvider::getInstance()->addListener(
            PrePersistEvent::class,
            $this->createAuditListener('CREATE')
        );
        
        ListenerProvider::getInstance()->addListener(
            PreUpdateEvent::class,
            $this->createAuditListener('UPDATE')
        );
        
        ListenerProvider::getInstance()->addListener(
            PreRemoveEvent::class,
            $this->createAuditListener('DELETE')
        );
    }
    
    private function createAuditListener(string $action): ListenerInterface
    {
        return new class($action) implements ListenerInterface {
            public function __construct(private string $action) {}
            
            public function execute(StoppableEventInterface $event): void
            {
                $entity = $event->getEntity();
                $userId = $this->getCurrentUserId();
                
                $record = [
                    'action' => $this->action,
                    'entity_type' => $entity::class,
                    'entity_id' => $entity->getId(),
                    'user_id' => $userId,
                    'timestamp' => (new DateTime())->format('Y-m-d H:i:s'),
                    'data' => serialize($entity),
                ];
                
                $this->auditLog->insert($record);
            }
            
            private function getCurrentUserId(): int
            {
                return $_SESSION['user_id'] ?? 0;
            }
        };
    }
}

// In application bootstrap:
(new AuditTrailService())->registerListeners();
```

### Best Practices

#### ✅ Do's

```php
✅ Keep listeners focused on one concern
class EmailNotificationListener implements ListenerInterface { }
class SearchIndexListener implements ListenerInterface { }

✅ Handle specific event types
if ($event instanceof PostPersistEvent) { }

✅ Use event bag for inter-listener communication
$event->addInBag('processed', true);

✅ Gracefully handle missing methods
if (method_exists($entity, 'setCreatedAt')) {
    $entity->setCreatedAt(now());
}

✅ Log listener errors
try {
    $this->notifyExternal($entity);
} catch (Exception $e) {
    $this->log->error('Notification failed', $e);
}
```

#### ❌ Don'ts

```php
❌ Don't perform long-running operations in listeners
// BAD
ListenerProvider::getInstance()->addListener(
    PostPersistEvent::class,
    new class implements ListenerInterface {
        public function execute($event) {
            sleep(10);  // Blocks request!
        }
    }
);

❌ Don't modify event outside your listener's scope
$event->getEntity()->delete();  // May cause issues

❌ Don't assume entity ID in PrePersistEvent
if ($event instanceof PrePersistEvent) {
    $id = $entity->getId();  // Still null!
}

❌ Don't rely on PostEvent if operation may fail
// Use Post events only for operations that succeeded

❌ Don't create circular listener dependencies
ListenerA → NotifyService → UpdateEntity → ListenerB → ...
```

---

## Comparing Both Approaches

### Approach 1: Direct Repository (Simple CRUD)

Best for: Standard CRUD operations with minimal business logic.

```php
class UserController extends AbstractController
{
    private UserRepository $repo;

    public function __construct()
    {
        $this->repo = new UserRepository();  // No EntityManager
    }

    public function update()
    {
        $user = $this->repo->find($_POST['id']);
        $user->setName($_POST['name']);
        $this->repo->save($user);  // Saves immediately
        return $this->returnJson($user);
    }
}
```

**Pros:**
- Simple and straightforward
- Direct control over persistence
- Minimal overhead

**Cons:**
- No automatic relationship sync
- Multiple transactions if saving related entities
- Must manage relationships manually

### Approach 2: EntityManager (Unit of Work)

Best for: Complex business logic with multiple related entities.

```php
class OrderService
{
    private EntityManager $em;

    public function __construct()
    {
        $this->em = EntityManager::getInstance();
    }

    public function updateOrder($orderId, $data)
    {
        $order = $this->em->find(Order::class, $orderId);
        $order->setTotal($data['total']);

        foreach ($data['items'] as $itemData) {
            $item = new OrderItem();
            $item->setQuantity($itemData['qty']);
            $order->addOrderItem($item);  // Auto-synced
            $this->em->persist($item);
        }

        $this->em->persist($order);
        $this->em->flush();  // Single transaction for everything
        return $order;
    }
}
```

**Pros:**
- Automatic relationship synchronization
- All changes in single transaction
- Identity map prevents duplicates
- Dependency ordering automatic

**Cons:**
- Slight learning curve
- More memory for tracking
- All-or-nothing flush semantics

---

## Usage in Controllers

### Example: UserController (Traditional Repository)

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

### Example: OrderController (EntityManager)
Controllers are already built ith their own EntityManager. No need to create another. 
```php
namespace App\Controllers;

use App\Entities\Order;
use App\Entities\OrderItem;
use App\Kernel\Connector\Management\EntityManager;
use App\Kernel\AbstractController;

class OrderController extends AbstractController
{
    private EntityManager $em;

    public function __construct()
    {
        parent::__construct();
    }

    // CREATE - Place new order with items
    public function create()
    {
        $orderData = $this->request->getAllDatas();

        // Create order
        $order = new Order();
        $order->setStatus('pending');
        $order->setTotal(0);

        // Add items
        $total = 0;
        foreach ($orderData['items'] as $itemData) {
            $item = new OrderItem();
            $item->setPrice($itemData['price']);
            $item->setQuantity($itemData['qty']);
                    
            // Auto-syncs both directions
            $order->addOrderItem($item);
            
            $this->em->persist($item);
            $total += $itemData['price'] * $itemData['qty'];
        }

        $order->setTotal($total);
        $this->em->persist($order);
        $this->em->flush();  // Everything persisted in one transaction

        return $this->returnJson(['id' => $order->getId()], 201);
    }

    // UPDATE - Modify order and items using transactionnal functionnality
    public function update()
    {
        $id = $this->request->getData('id');
        $orderData = $this->request->getAllDatas();

        try {
            $this->em->transactional(function() use ($id, $orderData) {
                $order = $this->em->find(Order::class, $id);
                if (!$order) {
                    throw new Exception('Order not found');
                }

                $order->setStatus($orderData['status']);
                
                // EntityManager tracks all changes
                $this->em->persist($order);
                $this->em->flush();
            });

            return $this->returnJson(null, 204);
        } catch (Exception $e) {
            return $this->returnError(500);
        }
    }

    // READ - Get order with all items
    public function read()
    {
        $id = $this->request->getData('id');
        $order = $this->em->find(Order::class, $id);

        if (!$order) {
            return $this->returnError(404);
        }

        return $this->returnJson([
            'id' => $order->getId(),
            'status' => $order->getStatus(),
            'items' => $order->getOrderItems(),
        ]);
    }

    // DELETE - Remove order (and cascade to items)
    public function delete()
    {
        $id = $this->request->getData('id');
        $order = $this->em->find(Order::class, $id);

        if (!$order) {
            return $this->returnError(404);
        }

        $this->em->remove($order);
        $this->em->flush();

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

⚠️ **CRITICAL**: Choose ONE approach per request:
- Use **EntityManager** for operations with multiple related entities
- Use **Repository** for simple CRUD operations
- **NEVER MIX** them in the same request cycle

### 1. Choose Your Pattern

**Use EntityManager when:**
- Multiple entities are created/updated in one operation
- You need automatic relationship synchronization
- You want all-or-nothing transaction semantics

```php
// ✓ GOOD - EntityManager for multi-entity operation
$em = EntityManager::getInstance();
$order = new Order();
$item = new OrderItem();
$order->addOrderItem($item);
$em->persist($order);
$em->persist($item);
$em->flush();  // Both saved together
```

**Use Repository when:**
- CRUD operations are simple and independent
- You want direct control over persistence
- You're working with a single entity type

```php
// ✓ GOOD - Repository for simple update
$repo = new UserRepository();
$user = $repo->find(1);
$user->setName('John');
$repo->save($user);  // Direct save
```

### 2. Business Logic in Repository

Centralize complex search logic in repository methods:

```php
// ✓ GOOD
public function findActiveUsersByRole(string $role): array
{
    return $this->findBy(['role' => $role, 'active' => true]);
}

// ✗ BAD - Logic scattered in controller
```

### 3. Data Validation

Validate entities before persisting:

```php
// ✓ GOOD - EntityManager
if (!$this->isValidUser($user)) {
    throw new ValidationException("Invalid user data");
}
$this->em->persist($user);
$this->em->flush();

// ✓ GOOD - Repository
if (!$this->isValidUser($user)) {
    return $this->returnError(422);
}
$this->repo->save($user);
```

### 4. Error Handling

Handle exceptions properly:

```php
// With EntityManager
try {
    $this->em->flush();
} catch (DatabaseException $e) {
    // Transaction rolled back automatically
    return $this->returnError(500);
}

// With Repository
try {
    $result = $this->repo->save($user);
    if (!$result) {
        throw new DatabaseException("Failed to save");
    }
} catch (DatabaseException $e) {
    return $this->returnError(500);
}
```

### 5. Sensitive Properties

Use `#[NotStored]` for temporary properties:

```php
#[NotStored]
private ?string $newpassword = null;

// In repository's save method
if (null !== $entity->getNewPassword()) {
    $hashedPassword = password_hash($entity->getNewPassword(), PASSWORD_DEFAULT);
    $entity->setPassword($hashedPassword);
}
```

### 6. Using Hydrator

Populate entities from request data:

```php
$user = Hydrator::hydrate(new User(), $requestData);
```

### 7. Strong Typing

Use type hints for clarity:

```php
public function save(EntityInterface $entity): null | false | EntityInterface
{
    if (!$entity instanceof User) {
        throw new InvalidArgumentException('Entity must be User');
    }
    // ...
}
```

### 8. Relationship Management

Use automatic sync methods:

```php
// ✓ GOOD - Automatic bidirectional sync
$author->addPost($post);  // Sets both author->posts and post->author

// ✗ AVOID - Manual sync (error-prone) when no syncRelation is set in SetAuthor
$post->setAuthor($author);  // Only one direction
// Must remember to do: $author->getPosts()->add($post);
```

### 9. Transaction Boundaries


**Repository:** Each save is a transaction

```php
$repo->save($user);  // Transaction: this save
// No relationship with other saves
```

### 10. Identity Map Usage

**EntityManager:** Identity map is automatic

```php
$user1 = $em->find(User::class, 1);
$user2 = $em->find(User::class, 1);

assert($user1 === $user2);  // ✓ Same object instance
```

**Repository:** No identity map

```php
$user1 = $repo->find(1);
$user2 = $repo->find(1);

assert($user1 !== $user2);  // Different instances
```

---

## Complete Workflows

### Workflow 1: Repository Pattern (Single Entity)

#### Creating a User

```php
$repo = new UserRepository();

$user = new User();
$user->setName('Smith');
$user->setFirstname('John');
$user->setUsername('jsmith');
$user->setNewPassword('secure123');

$savedUser = $repo->save($user);  // INSERT
return $this->returnJson(['id' => $savedUser->getId()], 201);
```

#### Retrieving a User

```php
$repo = new UserRepository();
$user = $repo->find(1);

if (!$user) {
    return $this->returnError(404);
}
return $this->returnJson(['name' => $user->getName()]);
```

#### Updating a User

```php
$repo = new UserRepository();
$user = $repo->find(1);
if (!$user) {
    return $this->returnError(404);
}

$user->setName('New Name');
$repo->save($user);  // UPDATE
return $this->returnJson(null, 204);
```

#### Deleting a User

```php
$repo = new UserRepository();
$user = $repo->find(1);
if (!$user) {
    return $this->returnError(404);
}

$repo->delete($user);  // DELETE
return $this->returnJson(null, 204);
```

### Workflow 2: EntityManager Pattern (Multiple Related Entities)

#### Creating Order with Items

```php
$em = EntityManager::getInstance();

$em->transactional(function() use ($em) {
    $order = new Order();
    $order->setStatus('pending');
    $order->setTotal(0);
    $em->persist($order);

    foreach ($itemsData as $itemData) {
        $item = new OrderItem();
        $item->setPrice($itemData['price']);
        $item->setQuantity($itemData['qty']);
        $order->addOrderItem($item);  // Auto-sync
        $em->persist($item);
    }

    $em->flush();  // All or nothing
});

return $this->returnJson(['id' => $order->getId()], 201);
```

#### Retrieving Order with Items

```php
$em = EntityManager::getinstance();
$order = $em->find(Order::class, 1);

if (!$order) {
    return $this->returnError(404);
}

// Items loaded via lazy bag
return $this->returnJson($order);
```

#### Updating Order and Items

```php
$em = EntityManager::getInstance();

$em->transactional(function() use ($em) {
    $order = $em->find(Order::class, 1);
    $order->setStatus('shipped');
    
    foreach ($order->getOrderItems() as $item) {
        $item->setStatus('shipped');
    }
    
    $em->flush();  // All changes atomic
});

return $this->returnJson(null, 204);
```

---

## Summary

| Concept | Description | Example |
|---------|-------------|---------|
| **Entity** | Class representing a business object | `User`, `Order` |
| **Repository** | Direct data persistence | `UserRepository::save()` |
| **EntityManager** | Unit of Work pattern - tracks & flushes | `$em->persist()`, `$em->flush()` |
| **Attributes** | Decorators to control behavior | `#[NotStored]`, `#[OneToMany]` |
| **Hydrator** | Fill entity from array | `Hydrator::hydrate($user, $data)` |
| **Identity Map** | One object per ID (EntityManager only) | `$em->find()` returns same instance |
| **Relationship Sync** | Automatic bidirectional sync | `addToCollection()`, `syncRelation()` |

### Quick Decision Tree

```
Are you working with multiple related entities?
├─ YES → Use EntityManager
│        └─ $em->persist(), $em->flush()
└─ NO  → Use Repository
         └─ $repo->save()
```

---

## Troubleshooting

### "I saved an entity but changes aren't visible"

**Repository:** Changes are persisted immediately.

```php
$user = $repo->find(1);
$user->setName('John');
$repo->save($user);  // ✓ Saved now
```

**EntityManager:** Must call `flush()`:

```php
$user = $em->find(User::class, 1);
$user->setName('John');
$em->persist($user);
$em->flush();  // ✓ Must flush
```

### "I'm getting duplicate entities"

This occurs when **mixing Repository and EntityManager**:

```php
// ✗ BAD - Mixing approaches
$em = EntityManager::getInstance();
$repo = new UserRepository();  // Without EntityManager!

$user1 = $em->find(User::class, 1);
$user2 = $repo->find(1);  // Different object!
// Now you have two different PHP objects for same DB entity
```

**Solution:** Pick one approach:

```php
// ✓ GOOD - EntityManager only
$em = EntityManager::getInstance();
$repo = new UserRepository($em);  // Pass EntityManager
$user = $repo->find(1);
```

### "Transaction rolled back unexpectedly"

This happens in EntityManager when any operation fails:

```php
$em->transactional(function() use ($em) {
    $order->save($order);
    $item->save($item);  // ← Exception here
    // Order save is ROLLED BACK
    $em->flush();
});
```

**Solution:** Validate before persisting:

```php
$em->transactional(function() use ($em) {
    if (!$this->validate($order)) {
        throw new ValidationException();
    }
    if (!$this->validate($item)) {
        throw new ValidationException();
    }
    $em->persist($order);
    $em->persist($item);
    $em->flush();  // All valid, all saved
});
```

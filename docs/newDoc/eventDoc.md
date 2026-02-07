# Event Management Documentation

The event management system in this framework follows PSR-14 standards and provides a robust, event-driven architecture for the application. This system allows you to hook into various kernel lifecycle stages and execute custom code through listeners.

## Table of Contents

1. [Overview](#overview)
2. [Core Concepts](#core-concepts)
3. [Architecture](#architecture)
4. [Interfaces](#interfaces)
5. [Available Kernel Events](#available-kernel-events)
6. [Creating Listeners](#creating-listeners)
7. [Event Configuration](#event-configuration)
8. [Priority System](#priority-system)
9. [Event Propagation](#event-propagation)
10. [Practical Examples](#practical-examples)
11. [Testing Events](#testing-events)

## Overview

The event system allows you to:
- Listen to specific kernel lifecycle events
- Execute custom code when events are triggered
- Control event propagation to stop further listeners
- Manage listener priorities to ensure execution order
- Decouple application logic through event-driven patterns

## Core Concepts

### Events

Events are objects that represent something happening in the application lifecycle. Each event:
- Extends `AbstractStoppableEvent`
- Implements `StoppableEventInterface`
- Represents a specific moment in the kernel lifecycle
- Can be stopped to prevent further listener execution

### Listeners

Listeners are handlers that respond to events. Each listener:
- Implements `ListenerInterface`
- Contains an `execute()` method
- Receives the event object
- Can modify application state based on event data

### EventDispatcher

The EventDispatcher is the central hub that:
- Retrieves listeners for a given event from the ListenerProvider
- Executes each listener in priority order
- Handles event propagation
- Runs listeners sequentially

### ListenerProvider

The ListenerProvider manages the registry of listeners:
- Stores listener-to-event mappings
- Sorts listeners by priority
- Retrieves listeners for specific events
- Acts as a singleton for global access

## Architecture

```
Kernel Lifecycle
       ↓
    Event Triggered
       ↓
EventDispatcher.dispatch(event)
       ↓
ListenerProvider.getListenersForEvent(event)
       ↓
Execute Listeners (sorted by priority, high to low)
       ↓
Each Listener.execute(event)
       ↓
Check isPropagationStopped()
       ↓
Return Modified Event
```

## Interfaces

### ListenerInterface

```php
namespace App\Kernel\Interfaces\Psr14;

interface ListenerInterface
{
    /**
     * Execute the listener handler for the event
     *
     * @param StoppableEventInterface $event The event to handle
     * @return void
     */
    public function execute(StoppableEventInterface $event): void;
}
```

All listeners must implement this interface with an `execute()` method.

### StoppableEventInterface

```php
namespace App\Kernel\Interfaces\Psr14;

interface StoppableEventInterface
{
    /**
     * Is propagation stopped?
     *
     * @return bool True if no further listeners should be called
     */
    public function isPropagationStopped(): bool;

    /**
     * Stop event propagation
     *
     * @return void
     */
    public function stopPropagation(): void;
}
```

### EventDispatcherInterface

```php
namespace App\Kernel\Interfaces\Psr14;

interface EventDispatcherInterface
{
    /**
     * Dispatch an event to all relevant listeners
     *
     * @param object $event The event to dispatch
     * @return object The event object (potentially modified)
     */
    public function dispatch(object $event);
}
```

### ListenerProviderInterface

```php
namespace App\Kernel\Interfaces\Psr14;

interface ListenerProviderInterface
{
    /**
     * Get all listeners for a specific event
     *
     * @param object $event The event instance
     * @return iterable Array of listeners
     */
    public function getListenersForEvent(object $event): iterable;
}
```

## Available Kernel Events

The framework provides several built-in kernel events triggered at specific lifecycle stages:

### InitKernelEvent

```php
namespace App\Kernel\Psr14\Events;

class InitKernelEvent extends AbstractStoppableEvent
{
    // Launched when kernel has boot (Request loaded)
}
```

**When:** Early in the kernel initialization, after the request has been loaded

**Use case:** Perform initialization tasks, set up global state, validate environment

### CallAuthKernelEvent

```php
namespace App\Kernel\Psr14\Events;

class CallAuthKernelEvent extends AbstractStoppableEvent
{
    // Launched when kernel needs User Authentication
}
```

**When:** Before authentication is needed

**Use case:** Authenticate users, check credentials, validate sessions

### CheckApiKeyKernelEvent

```php
namespace App\Kernel\Psr14\Events;

class CheckApiKeyKernelEvent extends AbstractStoppableEvent
{
    // Launched when kernel needs API Key usage authorisation
}
```

**When:** When API access needs to be verified

**Use case:** Validate API keys, check rate limits, enforce API permissions

### ConnectorKernelEvent

```php
namespace App\Kernel\Psr14\Events;

class ConnectorKernelEvent extends AbstractStoppableEvent
{
    // Launched when kernel needs database connection
}
```

**When:** Before getting database connection 

**Use case:** Setup database connections, validate credentials

### StartControllerKernelEvent

```php
namespace App\Kernel\Psr14\Events;

class StartControllerKernelEvent extends AbstractStoppableEvent
{
    // Launched when kernel call Controller
}
```

**When:** Just before the controller is invoked

**Use case:** Pre-controller checks, middleware processing

### ReturnResponseKernelEvent

```php
namespace App\Kernel\Psr14\Events;

class ReturnResponseKernelEvent extends AbstractStoppableEvent
{
    // Launched when kernel will return response to index
}
```

**When:** Just before the response is returned to the client

**Use case:** Response modification, logging, cleanup, response transformation

## Creating Listeners

### Basic Listener Implementation

Step 1: Create a class implementing `ListenerInterface`

```php
<?php

namespace App\Kernel\Psr14\Listener;

use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;

class MyCustomListener implements ListenerInterface
{
    public function execute(StoppableEventInterface $event): void
    {
        // Your listener logic here
        echo "Custom listener executed for event: " . get_class($event);
    }
}
```

### Listener with Dependencies

You can inject dependencies into your listener:

```php
<?php

namespace App\Kernel\Psr14\Listener;

use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;
use App\Kernel\Interfaces\Databases\ConnectorInterface;

class DatabaseListener implements ListenerInterface
{
    public function __construct(private ConnectorInterface $connector)
    {
        // Dependencies are available after construction
    }

    public function execute(StoppableEventInterface $event): void
    {
        // Use the injected connector
        $this->connector->getConnection();
        //Do something with database
    }
}
```

### Listening to Specific Event Types

To handle specific events, check the event type in your listener:

```php
<?php

namespace App\Kernel\Psr14\Listener;

use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;
use App\Kernel\Psr14\Events\CallAuthKernelEvent;
use App\Kernel\Psr14\Events\ConnectorKernelEvent;

class MultiEventListener implements ListenerInterface
{
    public function execute(StoppableEventInterface $event): void
    {
        if ($event instanceof CallAuthKernelEvent) {
            $this->handleAuth($event);
        } elseif ($event instanceof ConnectorKernelEvent) {
            $this->handleConnector($event);
        }
    }

    private function handleAuth(CallAuthKernelEvent $event): void
    {
        // Handle authentication
    }

    private function handleConnector(ConnectorKernelEvent $event): void
    {
        // Handle database connection
    }
}
```

### Accessing Application Context

Listeners can access the current request and application state:

```php
<?php

namespace App\Kernel\Psr14\Listener;

use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;
use App\Kernel\Request;

class RequestAwareListener implements ListenerInterface
{
    public function execute(StoppableEventInterface $event): void
    {
        $request = Request::getRequestInstance();
        $method = $request->getMethod();
        
        // React based on request details
        if ($method === 'POST') {
            // Handle POST-specific logic
        }
    }
}
```

## Event Configuration

Events are configured in the `Events` configuration class located at `App\Kernel\Config\Events`.

### Configuration File Structure

```php
<?php

namespace App\Kernel\Config;

use App\Kernel\Middleware\Security\AuthManagerMiddleware;
use App\Kernel\Psr14\Events\CallAuthKernelEvent;

class Events
{
    /**
     * Get all event listeners configuration
     *
     * Event array structure:
     * [
     *     EventClass::class => [
     *         new ListenerInstance1(), // priority 0
     *         new ListenerInstance2(), // priority 1
     *     ],
     *     AnotherEventClass::class => [
     *         new ListenerInstance3(), // priority 0
     *     ]
     * ]
     *
     * Note: Array order represents priority - index number = priority
     * Higher priority listeners execute first within their priority level
     *
     * Available Kernel Events:
     * - InitKernelEvent
     * - CallAuthKernelEvent
     * - CheckApiKeyKernelEvent
     * - ConnectorKernelEvent
     * - ReturnResponseKernelEvent
     * - StartControllerKernelEvent
     */
    public static function getListeners(): array
    {
        $events = [
            CallAuthKernelEvent::class => [
                new AuthManagerMiddleware()
            ]
        ];
        return $events;
    }
}
```

### Manual Listener Registration

You can also register listeners directly using the `ListenerProvider`:

```php
<?php

use App\Kernel\Psr14\Listener\ListenerProvider;
use App\Kernel\Psr14\Events\InitKernelEvent;
use App\Kernel\Psr14\Listener\MyCustomListener;

$provider = ListenerProvider::getInstance();

// Register a listener
$listener = new MyCustomListener();
$provider->addListener(InitKernelEvent::class, $listener, 5);
```

## Priority System

### How Priority Works

- **Higher priority values execute first**
- Listeners with the same priority execute in registration order
- Default priority is 0 if not specified

### Priority Levels Example

```php
$events = [
    CallAuthKernelEvent::class => [
        new HighPriorityListener(),      // Index 0 = Priority 0 (lowest)
        new MediumPriorityListener(),    // Index 1 = Priority 1
        new CriticalListener(),          // Index 2 = Priority 2 (highest)
    ]
];
```

### Execution Order

When the event is dispatched, listeners execute from highest to lowest priority:

```php
// Configuration
$provider->addListener(CallAuthKernelEvent::class, $listener1, 10);
$provider->addListener(CallAuthKernelEvent::class, $listener2, 5);
$provider->addListener(CallAuthKernelEvent::class, $listener3, 20);

// Execution order: listener3 (priority 20) → listener1 (priority 10) → listener2 (priority 5)
```

### Programmatic Priority

When using the configuration array in `Events.php`, the array index becomes the priority:

```php
public static function getListeners(): array
{
    return [
        InitKernelEvent::class => [
            0 => new LoggingListener(),        // Priority 0
            1 => new ValidationListener(),     // Priority 1
            2 => new CriticalListener(),       // Priority 2
        ]
    ];
}
```

## Event Propagation

### Stopping Event Propagation

Event propagation can be stopped to prevent remaining listeners from executing:

```php
<?php

namespace App\Kernel\Psr14\Listener;

use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;
use App\Kernel\Psr14\Events\CallAuthKernelEvent;

class AuthBlockingListener implements ListenerInterface
{
    public function execute(StoppableEventInterface $event): void
    {
        if ($event instanceof CallAuthKernelEvent) {
            // Check authentication
            if (!$this->isAuthenticated()) {
                // Stop propagation - no further listeners will execute
                $event->stopPropagation();
                return;
            }
        }
    }

    private function isAuthenticated(): bool
    {
        // Your authentication logic
        return false;
    }
}
```

### Checking Propagation Status

Listeners can check if propagation is stopped:

```php
public function execute(StoppableEventInterface $event): void
{
    if ($event->isPropagationStopped()) {
        // Event propagation was stopped by a previous listener
        return;
    }

    // Continue with your logic
}
```

### Real-World Propagation Scenario

```php
// Configuration
$events = [
    CallAuthKernelEvent::class => [
        new AccessValidator(),        // Priority 2 - checks access (may stop)
        new UserLoader(),            // Priority 1 - loads user data
        new LoggingListener(),        // Priority 0 - logs activity
    ]
];

// When AccessValidator stops propagation:
// 1. AccessValidator.execute() - validates access, calls stopPropagation()
// 2. UserLoader.execute() - SKIPPED (propagation stopped)
// 3. LoggingListener.execute() - SKIPPED (propagation stopped)
```

## Practical Examples

### Example 1: Session-Based Authentication

```php
<?php

namespace App\Kernel\Psr14\Listener;

use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;
use App\Kernel\Psr14\Events\CallAuthKernelEvent;
use App\Kernel\Request;
use App\Security\User;

class SessionAuthListener implements ListenerInterface
{
    public function execute(StoppableEventInterface $event): void
    {
        if ($event instanceof CallAuthKernelEvent) {
            $request = Request::getRequestInstance();
            $userId = $request->getSessionValue('user_id');

            if ($userId === null) {
                // Not authenticated - stop propagation
                $event->stopPropagation();
                return;
            }

            // User is authenticated
            $user = User::findById($userId);
            if ($user === null) {
                // User not found in database
                $event->stopPropagation();
                return;
            }

            // Authentication successful
        }
    }
}
```

### Example 2: API Key Validation

```php
<?php

namespace App\Kernel\Psr14\Listener;

use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;
use App\Kernel\Psr14\Events\CheckApiKeyKernelEvent;
use App\Kernel\Request;

class ApiKeyValidator implements ListenerInterface
{
    private const VALID_KEYS = ['key123', 'key456', 'key789'];

    public function execute(StoppableEventInterface $event): void
    {
        if ($event instanceof CheckApiKeyKernelEvent) {
            $request = Request::getRequestInstance();
            $apiKey = $request->getHeaders('X-API-Key');

            if ($apiKey === null || !in_array($apiKey, self::VALID_KEYS, true)) {
                // Invalid API key
                $event->stopPropagation();
                return;
            }

            // API key is valid, continue
        }
    }
}
```

### Example 3: Request Logging

```php
<?php

namespace App\Kernel\Psr14\Listener;

use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;
use App\Kernel\Psr14\Events\InitKernelEvent;
use App\Kernel\Request;
use App\Kernel\Logger;

class RequestLogger implements ListenerInterface
{
    public function __construct(private Logger $logger)
    {
    }

    public function execute(StoppableEventInterface $event): void
    {
        if ($event instanceof InitKernelEvent) {
            $request = Request::getRequestInstance();

            $logMessage = sprintf(
                'Request: %s %s at %s',
                $request->getMethod(),
                $request->getUri(),
                date('Y-m-d H:i:s')
            );

            $this->logger->info($logMessage);
        }
    }
}
```

### Example 4: Complete Configuration File

```php
<?php

namespace App\Kernel\Config;

use App\Kernel\Psr14\Events\InitKernelEvent;
use App\Kernel\Psr14\Events\CallAuthKernelEvent;
use App\Kernel\Psr14\Events\CheckApiKeyKernelEvent;
use App\Kernel\Psr14\Events\ConnectorKernelEvent;
use App\Kernel\Psr14\Events\ReturnResponseKernelEvent;
use App\Kernel\Psr14\Listener\RequestLogger;
use App\Kernel\Psr14\Listener\SessionAuthListener;
use App\Kernel\Psr14\Listener\DatabaseInitializer;

class Events
{
    public static function getListeners(): array
    {
        return [
            // Initialize - log incoming requests
            InitKernelEvent::class => [
                new RequestLogger(),
            ],

            // Check API Key if needed
            CheckApiKeyKernelEvent::class => [
                new ApiKeyValidator(),
            ],

            // Authentication
            CallAuthKernelEvent::class => [
                new SessionAuthListener(),
            ],

            // Database connection
            ConnectorKernelEvent::class => [
                new DatabaseInitializer(),
            ],

            // Response processing
            ReturnResponseKernelEvent::class => [
                new ResponseLogger(),
                new SecurityHeadersListener(),
            ],
        ];
    }
}
```

## Testing Events

### Unit Testing Listeners

```php
<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;
use App\Kernel\Psr14\Events\InitKernelEvent;
use App\Kernel\Psr14\Listener\RequestLogger;

class RequestLoggerTest extends TestCase
{
    public function testListenerExecutesOnInitEvent(): void
    {
        $logger = $this->createMock(\App\Kernel\Logger::class);
        $logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Request:'));

        $listener = new RequestLogger($logger);
        $event = new InitKernelEvent();

        $listener->execute($event);
    }
}
```

### Unit Testing Event Dispatcher

```php
<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Psr14\Listener\ListenerProvider;
use App\Kernel\Psr14\Dispatcher\EventDispatcher;
use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Psr14\Events\InitKernelEvent;

class EventDispatcherTest extends TestCase
{
    public function testDispatcherExecutesListener(): void
    {
        // Create a mock listener
        $listener = $this->createMock(ListenerInterface::class);
        $listener->expects($this->once())
            ->method('execute');

        // Create provider and add listener
        $provider = new ListenerProvider();
        $provider->addListener(InitKernelEvent::class, $listener, 0);

        // Create dispatcher and dispatch event
        $dispatcher = new EventDispatcher($provider);
        $event = new InitKernelEvent();

        $dispatcher->dispatch($event);
    }

    public function testDispatcherRespectsPriority(): void
    {
        $listener1 = $this->createMock(ListenerInterface::class);
        $listener2 = $this->createMock(ListenerInterface::class);

        $provider = new ListenerProvider();
        $provider->addListener(InitKernelEvent::class, $listener2, 1);
        $provider->addListener(InitKernelEvent::class, $listener1, 2);

        $listeners = $provider->getListenersForEvent(new InitKernelEvent());

        // Higher priority (listener1) should be first
        $this->assertEquals($listener1, $listeners[0]);
        $this->assertEquals($listener2, $listeners[1]);
    }

    public function testDispatcherStopsOnPropagationStopped(): void
    {
        $listener1 = $this->createMock(ListenerInterface::class);
        $listener2 = $this->createMock(ListenerInterface::class);

        $listener1->method('execute')->willReturnCallback(function($event) {
            $event->stopPropagation();
        });

        $listener2->expects($this->never())
            ->method('execute');

        $provider = new ListenerProvider();
        $provider->addListener(InitKernelEvent::class, $listener1, 2);
        $provider->addListener(InitKernelEvent::class, $listener2, 1);

        $dispatcher = new EventDispatcher($provider);
        $dispatcher->dispatch(new InitKernelEvent());
    }
}
```

### Functional Testing Events

```php
<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Psr14\Listener\ListenerProvider;
use App\Kernel\Psr14\Dispatcher\EventDispatcher;
use App\Kernel\Psr14\Events\InitKernelEvent;

class EventsIntegrationTest extends TestCase
{
    public function testFullEventLifecycle(): void
    {
        // Setup
        $provider = ListenerProvider::getInstance();
        $dispatcher = EventDispatcher::getInstance($provider);

        // Clear previous listeners
        $provider->resetListeners();

        // Create a test listener
        $listener = new class implements \App\Kernel\Interfaces\Psr14\ListenerInterface {
            public $executed = false;

            public function execute(\App\Kernel\Interfaces\Psr14\StoppableEventInterface $event): void
            {
                $this->executed = true;
            }
        };

        // Register and dispatch
        $provider->addListener(InitKernelEvent::class, $listener);
        $event = new InitKernelEvent();
        $dispatcher->dispatch($event);

        // Assert
        $this->assertTrue($listener->executed);
    }
}
```

## Best Practices

1. **Keep Listeners Focused**: Each listener should handle one specific concern
2. **Use Proper Typing**: Always type hint event parameters with specific event classes
3. **Handle Exceptions Gracefully**: Wrap critical operations in try-catch blocks
4. **Document Event Dependencies**: Clearly document what each listener does
5. **Use Appropriate Priorities**: Set priorities based on execution order needs
6. **Stop Propagation Wisely**: Only stop propagation when necessary to prevent other handlers
7. **Inject Dependencies**: Use constructor injection for cleaner, testable code
8. **Log Important Operations**: Use the Logger class to track listener executions
9. **Test Thoroughly**: Create unit tests for all listeners
10. **Avoid Side Effects**: Listeners should be predictable and not have unexpected side effects

## Common Patterns

### Chain of Responsibility

Use priorities to create a chain of handlers:

```php
$events = [
    CallAuthKernelEvent::class => [
        new ValidateCredentials(),      // Priority 3 - First check
        new LoadUserProfile(),          // Priority 2 - Then load user
        new CheckPermissions(),         // Priority 1 - Then check permissions
        new LogAuthentication(),        // Priority 0 - Finally log the action
    ]
];
```

### Fallback Handling

Stop propagation on success to prevent fallback handlers:

```php
public function execute(StoppableEventInterface $event): void
{
    if ($this->canHandle($event)) {
        $this->handle($event);
        $event->stopPropagation(); // Prevent fallback handlers
    }
}
```



## Troubleshooting

### Listeners Not Executing

1. Check that listeners implement `ListenerInterface`
2. Verify events extend `AbstractStoppableEvent`
3. Ensure `MakeListener::applyListener()` is called at startup
4. Verify listener registration in `Events::getListeners()`

### Wrong Execution Order

1. Check priority values (higher = execute first)
2. Verify array index order in configuration
3. Remember that array index = priority value

### Propagation Issues

1. Check if previous listeners call `stopPropagation()`
2. Verify listener priorities don't have unintended conflicts
3. Use `isPropagationStopped()` to check status

## Error Handling

The event system throws `EventException` in these scenarios:

1. **Invalid event type**: Event doesn't implement `StoppableEventInterface`
2. **Invalid listener**: Listener doesn't implement `ListenerInterface`
3. **Non-existent event class**: Event class doesn't exist
4. **Missing dispatcher provider**: EventDispatcher initialized without a provider

Example error handling:

```php
<?php

use App\Kernel\Psr14\Exceptions\EventException;

try {
    $provider->addListener('NonExistentEvent', $listener);
} catch (EventException $e) {
    echo "Error registering listener: " . $e->getMessage();
}
```

## Conclusion

The event management system provides a clean, extensible architecture for handling kernel lifecycle events. By following PSR-14 standards, it ensures compatibility and consistency throughout the application. Use listeners to decouple business logic, improve testability, and create flexible, maintainable applications.

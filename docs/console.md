# Console System Documentation

## Overview

The Console System is a command-line interface (CLI) framework that allows developers to create and execute console commands from the terminal. It follows a hierarchical command structure with a main entry point (`console.php`) that routes commands to specialized command classes.

The system is designed with:
- **Modularity**: Commands are independent classes implementing a consistent interface
- **Hierarchy**: Commands can have sub-commands (e.g., `debug route`)
- **Extensibility**: Easy to add new commands by creating new classes
- **Helper Utilities**: Built-in formatting and discovery of available commands

---

## Architecture

### File Structure

```
bin/
├── console.php              # Main entry point
├── Console.php              # Main command dispatcher
├── AbstractConsole.php      # Base class for commands
├── ConsoleInterface.php     # Command interface
├── ConsoleHelper.php        # Helper utilities
└── Debug/
    └── Route.php            # Example: Debug Route command
```

---

## Core Components

### ConsoleInterface

The `ConsoleInterface` defines the contract for all console commands.

```php
namespace App\bin;

interface ConsoleInterface
{
    public function __construct(array $args, int $count);
    public function execute(): void;
}
```

**Contract:**
- `__construct(array $args, int $count)`: Initialize command with arguments
- `execute(): void`: Execute the command logic

### AbstractConsole

The `AbstractConsole` abstract class provides common functionality for all commands.

```php
abstract class AbstractConsole implements ConsoleInterface
{
    protected string $helpText = '';          // Help text for the command
    protected string $spaceName = '';         // Namespace for sub-commands
    protected string $directory = __DIR__;    // Directory containing sub-commands
    protected int $minArgs = 1;               // Minimum required arguments
    protected array $args;                    // Command arguments

    abstract public function execute(): void;
    
    protected function help(?bool $displayCmd = true): void
    protected function displayError(string $error, int $code): void
    protected function isCommandValid(string $command): ConsoleInterface | false
}
```

**Features:**
- Argument count validation
- Built-in help display
- Error handling with formatted messages
- Sub-command validation and execution
- Command discovery for sub-commands

### ConsoleHelper

The `ConsoleHelper` provides utility functions for console commands.

```php
class ConsoleHelper
{
    // Format text with ANSI color codes
    public static function makeSpecial(string $text, string $color, string $font): string
    
    // Discover available commands in a namespace
    public static function getCommands(string $spaceName, string $directory): array
}
```

#### Text Formatting

Create colored and styled text output using ANSI escape codes.

**Available Colors:**
- `black`, `red`, `green`, `yellow`, `blue`, `magenta`, `cyan`, `white`

**Available Fonts:**
- `reset`, `bold`, `underline`, `reverse`

```php
use App\bin\ConsoleHelper;

// Red text with bold style
$error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
echo "{$error} Something went wrong\n";

// Green text normal
$success = ConsoleHelper::makeSpecial('Success', 'green', 'reset');
echo "{$success} Operation completed\n";

// Blue reversed text
$info = ConsoleHelper::makeSpecial('Info', 'blue', 'reverse');
echo "{$info} Important information\n";
```

#### Command Discovery

Automatically discover all available commands in a namespace.

```php
$commands = ConsoleHelper::getCommands("App\\bin\\", __DIR__);
// Returns: ['Debug', 'Migrate', 'Seed', ...]

// Only returns:
// - Existing classes
// - Instantiable classes (not abstract)
// - Classes implementing ConsoleInterface
```

### ConsoleInterface Implementation

```php
class ConsoleHelper
{
    public static function getCommands(string $spaceName, string $directory): array
    {
        $commands = glob($directory . "/*.php");
        $cmdList = [];
        
        foreach ($commands as $command) {
            $className = basename($command, '.php');
            $fullName = $spaceName . $className;
            
            // Ensure class exists
            if (!class_exists($fullName)) {
                require_once $command;
            }
            
            if (!class_exists($fullName)) {
                continue; 
            }
            
            // Check if instantiable
            $reflection = new ReflectionClass($fullName);
            if (!$reflection->isInstantiable()) {
                continue;
            }
            
            // Check if implements ConsoleInterface
            if (is_a($fullName, ConsoleInterface::class, true)) {
                $cmdList[] = $className;
            }
        }
        
        return $cmdList;
    }
}
```

---

## Main Console Entry Point

### console.php

The `console.php` file is the main entry point for all console commands.

```bash
#!/usr/bin/env php
php ./bin/console.php command [args...]
```

**Usage:**

```bash
# Display help
php ./bin/console.php help

# Debug routes
php ./bin/console.php debug route

# Debug with help
php ./bin/console.php debug help
```

**Flow:**

1. **Autoloader**: Registers the vendor autoloader
2. **Argument Validation**: Checks for minimum required arguments
3. **Command Lookup**: Searches for the command class in `App\bin\` namespace
4. **Instantiation**: Creates an instance of the command class
5. **Validation**: Verifies the command implements `ConsoleInterface`
6. **Execution**: Calls the `execute()` method

**Error Handling:**

- Too few arguments → Display error and help
- Command not found → Display error and help
- Command not valid → Display error and help
- Exception during execution → Display error message and help

```php
if (2 > $argc) {
    displayError('too few arguments', 0);
}

$first = strtolower($argv[1] ?? '');

if ('help' === $first) {
    help();
}

$class = ucfirst($first);
$fullname = 'App\\bin\\' . $class;

if (!class_exists($fullname)) {
    displayError("Command {$first} does not exist", 0);
}

$args = array_slice($argv, 2);
$console = new $fullname($args, $argc - 2);

if (!($console instanceof ConsoleInterface)) {
    displayError("Command {$first} is not a valid command", 0);
}

$console->execute();
```

---

## Creating Commands

### Simple Command Example

Create a new command by extending `AbstractConsole`:

```php
<?php

namespace App\bin;

class Seed extends AbstractConsole
{
    protected string $helpText = <<<TEXT
    Seed database with initial data
    Usage: php ./bin/console seed
    TEXT;
    
    protected int $minArgs = 0;

    public function execute(): void
    {
        echo "Seeding database...\n";
        // Seed logic here
        echo "Done!\n";
    }
}
```

**Requirements:**
1. Extend `AbstractConsole`
2. Implement `execute()` method
3. Set `$helpText` for help command
4. Set `$minArgs` if command requires arguments

### Hierarchical Command Example: Debug

The `Debug` command demonstrates a parent command with sub-commands.

```php
<?php

namespace App\bin;

class Debug extends AbstractConsole
{
    // Namespace where sub-commands are located
    protected string $spaceName = 'App\\bin\\Debug\\';
    
    // Directory containing sub-commands
    protected string $directory = __DIR__ . DIRECTORY_SEPARATOR . 'Debug';

    public function __construct(array $args, int $count)
    {
        $cmd = ConsoleHelper::makeSpecial("\$cmd", 'blue', 'reverse');
        $this->helpText = <<<TEXT
        Using debug: php ./bin/console debug $cmd
        For help type: php ./bin/console debug help
        LIST of available commands:
        TEXT;
        
        parent::__construct($args, $count);
    }

    public function execute(): void
    {
        // Validate and execute sub-command
        $console = $this->isCommandValid($this->args[0]);
        if ($console) {
            $console->execute();
        }
    }
}
```

**Key Features:**
- Sets namespace and directory for sub-commands
- Custom help text with colored formatting
- Delegates to sub-command for actual execution

### Sub-Command Example: Debug\Route

```php
<?php

namespace App\bin\Debug;

use App\bin\AbstractConsole;
use App\Kernel\Config\Router;

class Route extends AbstractConsole
{
    protected int $minArgs = 0;  // No required arguments

    public function execute(): void
    {
        $this->displayHelp();
        $routes = $this->getRoutes();
        
        // Prepare table headers
        $headers = ['URI', 'METHOD', 'CONTROLLER', 'FUNCTION'];
        
        // Convert route data to table rows
        $arrayDisplay = [];
        foreach ($routes as $route => $params) {
            $routeLines = $this->makeLines($route, $params);
            $arrayDisplay = array_merge($arrayDisplay, $routeLines);
        }

        // Display formatted table
        $this->displayArray($headers, $arrayDisplay);
    }

    private function displayHelp(): void
    {
        $help = $this->args[0] ?? '';
        if ('help' === $help) {
            $this->helpText = "Display routes used by SmallApiServer.";
            $this->help(false);
        }
    }

    private function getRoutes(): array
    {
        return Router::getRoutes();
    }

    private function makeLines(string $route, array $routeParam): array
    {
        $tempLine = [];
        foreach ($routeParam as $key => $value) {
            $controller = basename(str_replace('\\', '/', $value[0]));
            $line = [
                $route . '/',
                $key,
                $controller,
                $value[1]
            ];
            $tempLine[] = $line;
        }
        return $tempLine;
    }

    private function displayArray(array $headers, array $rows): void
    {
        echo "\nRoutes used by SmallApiServer:\n\n";
        
        $widths = $this->calculateSize($headers, $rows);
        $separator = $this->makeSeparator($widths);
        
        echo $separator;
        echo $this->displayLines($headers, $widths);
        echo $separator;
        
        foreach ($rows as $row) {
            echo $this->displayLines($row, $widths);
        }
        
        echo $separator;
    }

    private function calculateSize(array $header, array $lines): array
    {
        $widths = array_map('strlen', $header);
        foreach ($lines as $line) {
            foreach ($line as $i => $cell) {
                $widths[$i] = max($widths[$i], strlen((string)$cell));
            }
        }
        return $widths;
    }

    private function makeSeparator(array $widths): string
    {
        $line = '+';
        foreach ($widths as $width) {
            $line .= str_repeat('-', $width + 1) . '+';
        }
        return $line . "\n";
    }

    private function displayLines(array $row, array $widths): string
    {
        $line = '|';
        foreach ($row as $i => $cell) {
            $line .= ' ' . str_pad((string)$cell, $widths[$i]) . '|';
        }
        return $line . "\n";
    }
}
```

**Features:**
- Displays routes in a formatted ASCII table
- Extracts controller names from full class names
- Pads columns for alignment
- Optional help display

---

## Usage Examples

### Display Help

```bash
php ./bin/console.php help
```

Output:
```
Console usage:
php ./bin/console.php command value1 value2
ex: 
php ./bin/console.php debug route
This will display the routes used by smallAPI
for help type: php ./bin/console.php help
Available commands:
- Debug
```

### Debug Commands

```bash
# Display all routes
php ./bin/console.php debug route

# Display routes help
php ./bin/console.php debug route help

# Display debug help
php ./bin/console.php debug help
```

Output example:
```
Routes used by SmallApiServer:

+--------+-------+-----------+----------+
|URI     |METHOD |CONTROLLER |FUNCTION  |
+--------+-------+-----------+----------+
|user/   |GET    |User       |get       |
|user/   |POST   |User       |add       |
|user/   |PUT    |User       |update    |
|user/   |DELETE |User       |delete    |
+--------+-------+-----------+----------+
```

---

## Best Practices

### 1. Set Minimum Arguments

Validate required arguments in the constructor or with `minArgs`:

```php
// ✓ GOOD - Validates minimum arguments
class Generate extends AbstractConsole
{
    protected int $minArgs = 2;  // Requires 2+ arguments
    
    public function execute(): void
    {
        $type = $this->args[0];      // Guaranteed to exist
        $name = $this->args[1];      // Guaranteed to exist
        // ...
    }
}

// ✗ BAD - No argument validation
class Generate extends AbstractConsole
{
    public function execute(): void
    {
        $type = $this->args[0];  // Might not exist
        $name = $this->args[1];  // Might not exist
    }
}
```

### 2. Provide Clear Help Text

Always provide detailed help text:

```php
// ✓ GOOD - Clear help with examples
protected string $helpText = <<<TEXT
Generate a new model
Usage: php ./bin/console generate model UserModel

Arguments:
  model-name    Name of the model to generate

Options:
  --timestamps  Add created_at and updated_at fields
  --migration   Generate migration file

Examples:
  php ./bin/console generate model User
  php ./bin/console generate model Post --timestamps
TEXT;
```

### 3. Handle Sub-Commands Properly

For parent commands with sub-commands:

```php
// ✓ GOOD - Proper sub-command handling
public function execute(): void
{
    if (0 === count($this->args)) {
        $this->help();  // Show help if no sub-command
    }
    
    $console = $this->isCommandValid($this->args[0]);
    if ($console) {
        $console->execute();
    }
}
```

### 4. Use ConsoleHelper for Formatting

Create consistent, colorful output:

```php
// ✓ GOOD - Formatted output
$success = ConsoleHelper::makeSpecial('✓', 'green', 'bold');
$error = ConsoleHelper::makeSpecial('✗', 'red', 'bold');
$info = ConsoleHelper::makeSpecial('ℹ', 'blue', 'reset');

echo "{$success} Operation completed\n";
echo "{$error} An error occurred\n";
echo "{$info} For more info, use --help\n";
```

### 5. Proper Error Handling

Use error display method for consistency:

```php
// ✓ GOOD - Consistent error handling
if (!$model) {
    $this->displayError("Model not found: {$name}", 1);
}

// ✗ BAD - Direct error output
if (!$model) {
    echo "Error: Model not found\n";
    die();
}
```

### 6. Organize Command Hierarchy

Use namespace hierarchy for logical grouping:

```
bin/
├── console.php
├── Generate.php          # Parent: Generate
├── Generate/
│   ├── Model.php         # Sub: generate model
│   ├── Migration.php     # Sub: generate migration
│   └── Seed.php          # Sub: generate seed
└── Debug/
    ├── Route.php         # Sub: debug route
    └── Cache.php         # Sub: debug cache
```

Usage:
```bash
php ./bin/console generate model User
php ./bin/console debug route
```

### 7. Make Commands Reusable

Separate command logic from presentation:

```php
// ✓ GOOD - Reusable logic
class Route extends AbstractConsole
{
    public function execute(): void
    {
        $routes = $this->getRoutes();
        $this->displayRoutes($routes);
    }
    
    public function getRoutes(): array
    {
        return Router::getRoutes();
    }
    
    private function displayRoutes(array $routes): void
    {
        // Display logic
    }
}

// Then in a controller, can call:
$command = new Route([], 0);
$routes = $command->getRoutes();
```

---

## Command Workflow

```
┌─────────────────────────────────────────────────────────┐
│ php ./bin/console.php debug route                       │
└──────────────────────┬──────────────────────────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │ console.php Entry Point      │
        │ - Load autoloader            │
        │ - Parse arguments            │
        └──────────────┬───────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │ Find Command Class           │
        │ - Locate: App\bin\Debug      │
        │ - Validate: implements       │
        │   ConsoleInterface           │
        └──────────────┬───────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │ Instantiate Debug            │
        │ - Pass args: ['route']       │
        │ - Validate minArgs           │
        └──────────────┬───────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │ Debug::execute()             │
        │ - Validate sub-command       │
        │ - Instantiate Route          │
        │ - Call Route::execute()      │
        └──────────────┬───────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │ Route::execute()             │
        │ - Get routes from Router     │
        │ - Format as table            │
        │ - Output to console          │
        └──────────────────────────────┘
```

---

## Complete Command Example

Here's a complete example of a migration command:

```php
<?php

namespace App\bin;

class Migrate extends AbstractConsole
{
    protected string $helpText = <<<TEXT
    Run database migrations
    
    Usage: php ./bin/console migrate [options]
    
    Options:
      refresh   Drop and re-run all migrations
      reset     Rollback all migrations
      status    Show migration status
    
    Examples:
      php ./bin/console migrate
      php ./bin/console migrate refresh
      php ./bin/console migrate status
    TEXT;
    
    protected int $minArgs = 0;

    public function execute(): void
    {
        $option = $this->args[0] ?? 'run';
        
        try {
            match ($option) {
                'refresh' => $this->refresh(),
                'reset' => $this->reset(),
                'status' => $this->status(),
                'help' => $this->help(),
                default => $this->run(),
            };
        } catch (Exception $e) {
            $this->displayError($e->getMessage(), 1);
        }
    }

    private function run(): void
    {
        $success = ConsoleHelper::makeSpecial('✓', 'green', 'bold');
        echo "{$success} Running migrations\n";
        // Migration logic
        echo "{$success} Migrations completed\n";
    }

    private function refresh(): void
    {
        echo "Refreshing database...\n";
        $this->reset();
        $this->run();
    }

    private function reset(): void
    {
        echo "Resetting migrations...\n";
        // Reset logic
    }

    private function status(): void
    {
        echo "Migration status:\n";
        // Display status
    }
}
```

---

## Summary

| Component | Purpose | Example |
|-----------|---------|---------|
| **console.php** | Main entry point | `php ./bin/console.php debug route` |
| **Console.php** | Command dispatcher | Routes commands to classes |
| **AbstractConsole** | Base class | Extend to create commands |
| **ConsoleInterface** | Contract | Implement for valid commands |
| **ConsoleHelper** | Utilities | Format text, discover commands |
| **Debug** | Parent command | Has sub-commands (Route, etc.) |
| **Debug\Route** | Sub-command | Displays application routes |


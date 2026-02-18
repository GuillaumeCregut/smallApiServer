<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap File (without Composer)
 * Executed once before any tests run
 */

// 1. Manual autoloader for your classes
spl_autoload_register(function ($class) {
    // For App namespace classes in project root
    // Example: App\Mailer -> ../Mailer.php
    if (str_starts_with($class, 'App\\')) {
        $classFile = __DIR__ . '/../src/' . substr($class, 4) . '.php';
        if (file_exists($classFile)) {
            require_once $classFile;
            return;
        }
    }
    
    // For other namespaced classes in their respective directories
    $classFile = __DIR__ . '/../' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
        return;
    }
    
    // For test classes (tests directory)
    if (str_starts_with($class, 'Tests\\')) {
        $testFile = __DIR__ . '/' . str_replace('\\', '/', substr($class, 6)) . '.php';
        if (file_exists($testFile)) {
            require_once $testFile;
            return;
        }
    }
});

// 2. Set timezone to avoid warnings
date_default_timezone_set('UTC');

// 3. Define test-specific constants
define('TEST_ROOT', __DIR__);
define('PROJECT_ROOT', dirname(__DIR__));
define('FIXTURES_PATH', TEST_ROOT . '/fixtures');

// 4. Load test configuration
if (file_exists(__DIR__ . '/config/test-config.php')) {
    require_once __DIR__ . '/config/test-config.php';
}

// 5. Set up error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 6. Initialize test database or other resources
// Example: Set up SQLite in-memory database
if (!defined('DB_PATH')) {
    define('DB_PATH', ':memory:');
}

// 7. Register custom autoloader for test helpers if needed
spl_autoload_register(function ($class) {
    // Load test helpers, mocks, stubs, etc.
    if (str_starts_with($class, 'Tests\\')) {
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, 6)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// 8. Set up global test fixtures or mock data
// Example: Create test directories
$tempDir = sys_get_temp_dir() . '/phpunit-tests-' . uniqid();
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}
define('TEST_TEMP_DIR', $tempDir);

// 9. Register shutdown function to clean up
register_shutdown_function(function () {
    // Clean up temporary test files
    if (defined('TEST_TEMP_DIR') && is_dir(TEST_TEMP_DIR)) {
        array_map('unlink', glob(TEST_TEMP_DIR . '/*'));
        rmdir(TEST_TEMP_DIR);
    }
});

// 10. Load environment variables for testing (optional)
if (file_exists(PROJECT_ROOT . '/.env.testing')) {
    $lines = file(PROJECT_ROOT . '/.env.testing', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
        $_ENV[trim($key)] = trim($value);
    }
}

// 11. Initialize any global services needed for tests
// Example: Database connection pool, cache, etc.
class TestHelper
{
    private static ?PDO $db = null;
    
    public static function getTestDatabase(): PDO
    {
        if (self::$db === null) {
            self::$db = new PDO('sqlite::memory:');
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$db;
    }
    
    public static function resetDatabase(): void
    {
        self::$db = null;
    }
}

echo "PHPUnit bootstrap completed successfully.\n";
<?php

namespace App\Vendor;

class Autoload
{
    public static function register()
    {
        spl_autoload_register(function ($class) {
            $prefix = 'App\\';
            $baseDir = __DIR__ . '/../src/';

            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        });
    }
}
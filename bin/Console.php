#!/usr/bin/env php
<?php

use App\bin\ConsoleHelper;
use App\bin\ConsoleInterface;

$filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
require_once $filePath . 'Autoload.php';

App\Vendor\Autoload::register();

$space = "App\\bin\\";

if (2 > $argc) {
    displayError('too few arguments', 0);
}
$first = $argv[1] ?? null;
if (null === $first) {
    displayError('No command', 0);
}
$first = strtolower($first);
if ('help' === $first) {
    help();
}

$class = ucfirst($first) ?? null;
$fullname = $space . $class;

if (! class_exists($fullname)) {
    displayError("Command {$first} does not exists", 0);
}

$args = array_slice($argv, 2);
try{
    $console = new $fullname($args, $argc - 2);
} catch(Exception $e) {
    displayError("something went wrong with {$first} command", 0);
}

if (!($console instanceof ConsoleInterface)) {
    displayError("Command {$first} is not a valid command", 0);
}
try {
    $console->execute();
} catch (Exception $e) {
    displayError($e->getMessage(), (int)$e->getCode());
}

function help(?string $error = null): void
{
    $template = <<<PHP
    Console usage :
    php ./bin/console.php command value1 value2
    ex : 
    php ./bin/console.php debug route
    This will display the routes used by smallAPI
    for help type : php ./bin/console.php help
    Available commands :
    PHP;
    echo $template . "\n";
    foreach(ConsoleHelper::getCommands( "App\\bin\\", __DIR__) as $cmd){
        echo "- {$cmd}\n";
    }
    die();
}

function displayError(string $error, int $code): void
{
    $start = ConsoleHelper::makeSpecial('Error :', 'red', 'bold');
    echo "{$start} {$error} \n";
    help();
}



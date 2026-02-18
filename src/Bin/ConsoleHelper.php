<?php

namespace App\Bin;

use ReflectionClass;

class ConsoleHelper
{
    public static function makeSpecial(string $text, string $color, string $font): string
    {
        $colors = [
            'black' => 30,
            'red' => 31,
            'green' => 32,
            'yellow' => 33,
            'blue' => 34,
            'magenta' => 35,
            'cyan' => 36,
            'white' => 37
        ];
        $fonts = [
            'reset' => 0,
            'bold' => 1,
            'underline' => 4,
            'reverse' => 7
        ];
        $codeColor = $colors[$color] ?? 37;
        $codeFont = $fonts[$font] ?? 0;
        $newString = "\033[{$codeFont};{$codeColor}m{$text}\033[0m ";
        return $newString;
    }

    public static function getCommands(string $spaceName, string $directory): array
    {
        $commands = glob($directory . "/*.php");
        $cmdList = [];
        foreach ($commands as $command) {
            $className = basename($command, '.php');
            $fullName = $spaceName . $className;
            if (!class_exists($fullName)) {
                require_once $command;
            }
            if (!class_exists($fullName)) {
                continue; 
            }
            $reflection = new ReflectionClass($fullName);
            if (!$reflection->isInstantiable()) {
                continue;
            }
            if (is_a($fullName, ConsoleInterface::class, true)) {
                $cmdList[] = $className;
            }
        }
        return $cmdList;
    }
}

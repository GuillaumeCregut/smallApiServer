<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Form\Sanitizer;

class DataSanitizer
{

    private static array $stringRules = [
        'trim',
        'strip_tags',
        [self::class,'htmlSpecial']
    ];
    public static function sanitize(array $values): array
    {
        return array_map(fn($value)=> self::sanitizeValue($value), $values);
    }

    private static function sanitizeValue(mixed $value): mixed
    {
         return match(true) {
            is_string($value) => self::sanitizeString($value),
            is_array($value)  => self::sanitize($value),   
            default           => $value,                   
        };
    }

    private static function sanitizeString(string $value): string
    {
        foreach(self::$stringRules as $rule) {
            $value = $rule($value);
        }
        return $value;
    }

    private static function htmlSpecial(string $value): string
    {
        return htmlspecialchars($value, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

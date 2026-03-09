<?php

namespace App\Kernel\Form\Caster;

use ReflectionClass;

class DataCaster
{
    public static function cast(string $entity, array $values): array
    {
        $reflection = new ReflectionClass($entity);
        foreach ($values as $key => $value) {
            if (!$reflection->hasProperty($key)) {
                continue;
            }
            $type = $reflection->getProperty($key)->getType()?->getName();
            $values[$key] = self::castValue($value, $type);
        }
        return $values;
    }

    private static function castValue(mixed $value, ?string $type): mixed
    {
        if ($value === null) {
            return null;
        }
        return match ($type) {
            'int'    => (int) $value,
            'float'  => (float) $value,
            'bool'   => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string) $value,
            default  => $value, // null, unknown types → untouched
        };
    }
}

<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Form\Validator;

use App\Kernel\Connector\Interfaces\EntityInterface;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;
use ReflectionAttribute;

class DataValidator
{
    public static function validate(string $entity, array $values): bool
    {
        if (!is_subclass_of($entity, EntityInterface::class)) {
            throw new InvalidArgumentException('Entity must implements EntityInterface');
        }
        $reflexion = new ReflectionClass($entity);
        $properties = $reflexion->getProperties();
        foreach ($properties as $property) {
            $validate = self::validateProperty($property, $values);
            if (!$validate) {
                return false;
            }
        }
        return true;
    }


    private static function validateProperty(ReflectionProperty $property, array $values): bool
    {
        $name = $property->getName();
        $attributes = $property->getAttributes(ValidatorInterface::class, ReflectionAttribute::IS_INSTANCEOF);
        if (empty($attributes)) {
            return true;
        }
        if (!array_key_exists($name, $values)) {
            return false;
        }
        foreach ($attributes as $attribute) {
            $testAttribute = $attribute->newInstance();
            $valid = $testAttribute->validate($values[$name]);
            if (!$valid) {
                return false;
            }
        }
        return true;
    }
}

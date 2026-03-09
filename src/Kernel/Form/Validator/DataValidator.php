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

    /**
     * Validate incoming datas matching with asserts
     * If one field fails, return false
     *
     * @param string $entity
     * @param array $values
     * @param array $files
     * @return boolean
     */
    public static function validate(string $entity, array $values, array $files =[]): bool
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


    private static function validateProperty(ReflectionProperty $property, array $values, array $files=[]): bool
    {
        $name = $property->getName();
        $values = array_merge($values, $files);
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

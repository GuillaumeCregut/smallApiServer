<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Form\Validator;

use ReflectionClass;
use ReflectionProperty;
use ReflectionAttribute;
use InvalidArgumentException;
use App\Kernel\Form\Validator\Assert\IsNull;
use App\Kernel\Connector\Interfaces\EntityInterface;

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
    public static function validate(string $entity, array $values, array $files = []): bool
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


    private static function validateProperty(ReflectionProperty $property, array $values, array $files = []): bool
    {
        $name = $property->getName();
        $values = array_merge($values, $files);
        $attributes = $property->getAttributes(ValidatorInterface::class, ReflectionAttribute::IS_INSTANCEOF);
        if (empty($attributes)) {
            return true;
        }
        foreach ($attributes as $attribute) {
            $testAttribute = $attribute->newInstance();
            if (!array_key_exists($name, $values)) {
                if ($testAttribute instanceof IsNull) {
                    continue;
                } else {
                    return false;
                }
            }
            $valid = $testAttribute->validate($values[$name]);
            if (!$valid) {
                return false;
            }
        }
        return true;
    }
}

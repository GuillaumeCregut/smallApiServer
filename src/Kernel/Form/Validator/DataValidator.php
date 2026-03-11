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
use App\Kernel\Connector\Interfaces\EntityInterface;
use App\Kernel\Form\Validator\Assert\Optional;

class DataValidator
{

    private static array $errorMessages = [];

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
        self::$errorMessages = [];
        $values = array_merge($values, $files);
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
            foreach ($attributes as $attribute) {
                if ($attribute->newInstance() instanceof Optional) {
                    return true; // whole property skipped
                }
            }
            self::$errorMessages[$name] = ['error' => 'Property has assert but no value provided'];
            return false;
        }

        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            $testAttribute = $attribute->newInstance();
            if ($testAttribute instanceof Optional) {
                continue;
            }
            $valid = $testAttribute->validate($values[$name]);
            if (!$valid) {
                $message = $testAttribute->getMessage();
                $error = sprintf($message, $name);
                self::$errorMessages[$name] = array($attributeName => $error);
                return false;
            }
        }
        return true;
    }

    public static function getErrors(): array
    {
        return self::$errorMessages;
    }
}

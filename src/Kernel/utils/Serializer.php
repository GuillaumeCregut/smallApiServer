<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\utils;

use Exception;
use ReflectionClass;

class Serializer
{
    public static function serialize(object $object, array $unShow = [], int $depth = 0, int $maxDepth = 1): array
    {
        $reflexion = new ReflectionClass($object);
        $fieldsToHide = self::resolveFieldsToHide($object, $unShow);

        $objectProperties = [];

        $properties = [];
        do {
            foreach ($reflexion->getProperties() as $property) {
                $name = $property->getName();
                if (!isset($properties[$name])) { // child class takes priority
                    $properties[$name] = $property;
                }
            }
            $reflexion = $reflexion->getParentClass();
        } while (false !== $reflexion);

        foreach ($properties as $name => $property) {
            if (in_array($name, $fieldsToHide)) {
                continue;
            }
            if (!$property->isInitialized($object)) {
                continue;
            }
            try {
                $value = self::getValue($name, $property->isPublic(), $object, $unShow, $depth, $maxDepth);
                $objectProperties[$name] = $value;
            } catch (Exception $e) {
            }
        }
        return $objectProperties;
    }

    /**
     * Resolve which fields to hide for the given object,
     * walking up the class hierarchy (Liskov).
     */
    private static function resolveFieldsToHide(object $object, array $unshow): array
    {
        $fieldsToHide = [];
        $className = get_class($object);
        do {
            if (isset($unshow[$className])) {
                $fieldsToHide = array_merge($fieldsToHide, $unshow[$className]);
            }
            $className = get_parent_class($className);
        } while (false !== $className);
        return array_unique($fieldsToHide);
    }

    /**
     * Read the raw value of a property, either directly (public)
     * or via getter (private/protected).
     *
     * @throws Exception if no getter exists for a non-public property
     */
    private static function readValue(string $name, bool $visible, object $object): mixed
    {
        if ($visible) {
            return $object->$name;
        }
        $getter = 'get' . ucfirst($name);
        if (!method_exists($object, $getter)) {
            $objectName = get_class($object);
            throw new Exception("No getter found for property '{$name}' in object {$objectName}");
        }
        return $object->$getter();
    }

    private static function getValue(string $name, bool $visible, object $object, array $unShow, int $depth, int $maxDepth): mixed
    {
        $value =  self::readValue($name, $visible, $object);
        if (is_object($value)) {
            if ($depth < $maxDepth) {
                return self::serialize($value, $unShow, $depth + 1, $maxDepth);
            }
            return null;
        }
        if (is_array($value)) {
            foreach ($value as $key => $subValue) {
                if (is_object($subValue)) {
                    if ($depth < $maxDepth) {
                        $value[$key] = self::serialize($subValue, $unShow, $depth + 1, $maxDepth);
                    } else {
                        $value[$key] = null;
                    }
                }
            }
        }
        return $value;
    }
}

<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils;

use ReflectionClass;
use ReflectionProperty;
use InvalidArgumentException;
use App\Kernel\Connector\Attributes\Nullable;
use App\Kernel\Connector\Attributes\ManyToOne;
use App\Kernel\Connector\Attributes\NotStored;
use App\Kernel\Connector\Attributes\OneToMany;
use App\Kernel\Connector\Interfaces\EntityInterface;
use App\Kernel\Files\FileUpload;


class EntityAnalyzer
{

    public static function getAllEntitiesProperties(string $path): array
    {
        if (!is_dir($path)) {
            throw new InvalidArgumentException("Path {$path} does not exists");
        }
        $entities = [];
        $entitiesFiles = glob($path . '*.php');
        foreach ($entitiesFiles as $file) {
            require_once $file;
            $className = basename($file, '.php');
            $fqcn = self::getClassNamespace($file) . '\\' . $className;
            if (class_exists($fqcn)) {
                $properties = self::getStoredProperties($fqcn, true);
                $className = preg_replace('/Entity$/', '', $className);
                $className = Helper::propertyToColumn($className) . 's';
                $entities[$className] = $properties;
            }
        }
        return $entities;
    }

    public static function getStoredProperties(string $entity, bool $translated = false): array
    {
        if (!is_subclass_of($entity, EntityInterface::class)) {
            throw new InvalidArgumentException('Entity class must be an instance of EntityInterface');
        }
        $result = [];
        $reflection = new ReflectionClass($entity);
        if (!$reflection->isFinal()) {
            throw new InvalidArgumentException('Entity must be final Class');
        }
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            $propertyInfo = self::getPropertyInfo($property, $translated);
            if (null == $propertyInfo) {
                continue;
            }
            $name = $property->getName();
            if ($translated) {
                $name = Helper::propertyToColumn($name);
            }
            $result[$name] = $propertyInfo;
        }
        return $result;
    }

    private static function getPropertyInfo(ReflectionProperty $property, bool $translated): ?array
    {
        $name = $property->getName();
        if ($translated) {
            $name = Helper::propertyToColumn($name);
        }
        $notStored = $property->getAttributes(NotStored::class);
        if (!empty($notStored)) {
            return null;
        }
        $OneToMany = $property->getAttributes(OneToMany::class);
        if(!empty($OneToMany)) {
            return null;
        }

        $isNul = false;
        $nullable = $property->getAttributes(Nullable::class);
        if (!empty($nullable)) {
            $isNul = true;
        }
        $relation = [];
        $typeProperty = $property->getType()->getName();
        if (FileUpload::class === $typeProperty) {
            $typeProperty = 'string';
        }

        if ('array' === $typeProperty) {
            $typeProperty = 'json';
        }
        $manyToOne = $property->getAttributes(ManyToOne::class);
        if (!empty($manyToOne)) {
            if ($translated) {
                $typeProperty = preg_replace('/Entity$/', '', $typeProperty);
                $typeProperty = Helper::propertyToColumn($typeProperty) . 's';
            }
            $instance = $manyToOne[0]->newInstance();
            $relation['entity'] = $typeProperty;
            $ext = $translated ? '_id' : 'Id';
            $relation['key'] = $name . $ext;
            $relation['onDelete'] = strtoupper($instance->onDelete);
            $relation['onUpdate'] = strtoupper($instance->onUpdate);
            $typeProperty = 'relation';
        }
        return [
            'nullable' => $isNul,
            'type' => $typeProperty,
            'relation' => $relation
        ];
    }

    private static function getClassNameSpace(string $file): string
    {
        $tokens = token_get_all(file_get_contents($file));
        $namespace = '';
        foreach ($tokens as $i => $token) {
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                // Collect all tokens after T_NAMESPACE until semicolon
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if ($tokens[$j] === ';') {
                        break;
                    }
                    if (is_array($tokens[$j]) && $tokens[$j][0] !== T_WHITESPACE) {
                        $namespace .= $tokens[$j][1];
                    }
                }
                break;
            }
        }
        return $namespace;
    }
}

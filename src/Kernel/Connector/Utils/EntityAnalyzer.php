<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils;

use App\Kernel\Connector\Attributes\ManyToMany;
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
        $manyToMany = [];
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
                $relations = self::getManyToManyRelations($fqcn, true);
                if(!empty($relations)) {
                    $manyToMany[$className] = $relations;
                }
            }
        }
        return [
            'properties' => $entities,
            'manyToMany' => $manyToMany
        ];
    }

    public static function getManyToManyRelations(string $entity, bool $translated = false): array
    {
        $tests = self::testEntity($entity);
        if ($tests['error']) {
            throw new InvalidArgumentException($tests['message']);
        }
        $reflection = $tests['reflection'];
        $result = [];
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            $name = $property->getName();
            if ($translated) {
                $name = Helper::propertyToColumn($name);
            }
            $manyToManyArg = $property->getAttributes(ManyToMany::class);
            if (empty($manyToManyArg)) {
                continue;
            }
            /**@var ManyToMany $manyToMany */
            $manyToMany = $manyToManyArg[0]->newInstance();
            $tableOwner = basename(preg_replace('/Entity$/', '', $entity));
            $tableTarget = basename(preg_replace('/Entity$/', '', $manyToMany->targetEntity));
            if ($translated) {
                $tableOwner = Helper::propertyToColumn($tableOwner) . 's';
                $tableTarget = Helper::propertyToColumn($tableTarget) . 's';
            }
            $relations = [
                'tableName' => $manyToMany->pivotTable,
                'colOwner' => $manyToMany->ownerColumn,
                'colTarget' => $manyToMany->targetColumn,
                'tableOwner' => $tableOwner,
                'tableTarget' => $tableTarget
            ];
            $result[$name] = $relations;
        }
        return $result;
    }

    public static function getStoredProperties(string $entity, bool $translated = false): array
    {
        $result = [];
        $tests = self::testEntity($entity);
        if ($tests['error']) {
            throw new InvalidArgumentException($tests['message']);
        }
        $reflection = $tests['reflection'];
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

    private static function testEntity(string $entity): array
    {
        $error = false;
        $message = '';
        $reflection = null;
        if (!is_subclass_of($entity, EntityInterface::class)) {
            $message = 'Entity class must be an instance of EntityInterface';
            $error = true;
            return [
                'error' => $error,
                'message' => $message,
                'reflection' => $reflection
            ];
        }
        $reflection = new ReflectionClass($entity);
        if (!$reflection->isFinal()) {
            $message = 'Entity must be final Class';
            $error = true;
        }
        return [
            'error' => $error,
            'message' => $message,
            'reflection' => $reflection
        ];
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

        $manyToManyArg = $property->getAttributes(ManyToMany::class);
        if (!empty($manyToManyArg)) {
            return null;
        }

        $OneToMany = $property->getAttributes(OneToMany::class);
        if (!empty($OneToMany)) {
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
                $typeProperty = basename(preg_replace('/Entity$/', '', $typeProperty));
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

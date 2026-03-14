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
            $relation['entity'] = $typeProperty;
            $ext = $translated ? '_id' : 'Id';
            $relation['key'] = $name . $ext;
            $typeProperty = 'relation';
        }
        return [
            'nullable' => $isNul,
            'type' => $typeProperty,
            'relation' => $relation
        ];
    }

    
}

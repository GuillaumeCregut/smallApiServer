<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils;

use InvalidArgumentException;

class EntitySchemaTransformer
{

    private array $columns = [];
    private array $primaryKeys = [];
    private array $indexes = [];
    private string $entityName = '';
    private array $authorised = [
        'string',
        'int',
        'relation',
        'float',
        'datetime',
        'date',
        'time',
        'json',
        'bool'
    ];
    private array $authorisedConstraints = ['CASCADE', 'RESTRICT', 'SET NULL', 'NO ACTION', 'SET DEFAULT'];

    public function transform(string $entityName, array $properties): ?array
    {
        $this->columns = [];
        $this->primaryKeys = [];
        $this->indexes = [];

        $this->entityName = $entityName;
        if (empty($properties)) {
            return null;
        }
        foreach ($properties as $name => $config) {
            if ('' === $name) {
                throw new InvalidArgumentException("Entity {$entityName} : missing property name");
            }

            if (1 !== preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
                throw new InvalidArgumentException("Entity {$entityName} : unauthorised characters in property name {$name}");
            }
            $valid = self::testProperty($name, $config);
            if (!$valid['test']) {
                throw new InvalidArgumentException("Entity {$entityName} : {$valid['message']}");
            }
            $this->transformProperty($name, $config);
            if ('id' == $name) {
                $this->primaryKeys[] = $name;
            }
        }
        return [
            'columns' => $this->columns,
            'primary_keys' => $this->primaryKeys,
            'indexes' => $this->indexes
        ];
    }

    private function testProperty(string $name, array $config): array
    {
        $test = true;
        $message = '';

        if (!isset($config['type'])) {
            $message = "Property {$name} missing type";
            $test = false;
        } else if (!in_array($config['type'], $this->authorised)) {
            throw new InvalidArgumentException("Entity {$this->entityName}, property {$name} : missing type or bad type");
            $test = false;
            $message = 'missing type or bad type';
        }

        if (!isset($config['nullable'])) {
            $message = "Property {$name} missing nullable";
            $test = false;
        } else if (!is_bool($config['nullable'])) {
                $test = false;
                $message = ' wrong nullable type';
        }

        if (!isset($config['relation'])) {
            $message = "Property {$name} missing relation array";
            $test = false;
        }

        return [
            'test' => $test,
            'message' => $message
        ];
    }

    private function transformProperty(string $name, array $property): array
    {
        $formatted = [];
        $relations = $property['relation'];
        if ('relation' === $property['type']) {
            $formatted['type'] = 'int';
            $error = $this->testRelationValues($relations);
            if ($error['error']) {
                throw new InvalidArgumentException("Entity {$this->entityName}, property {$name} : {$error['message']}");
            }
            $formatted['fk'] = $relations['entity'];
            $formatted['onUpdate'] = strtoupper($relations['onUpdate']);
            $formatted['onDelete'] = strtoupper($relations['onDelete']);
            $name = $this->makeIndex($name, $relations);
        } else {
            $formatted['type'] = $property['type'];
        }
        $formatted['nullable'] = $property['nullable'];
        $this->columns[$name] = $formatted;
        return $formatted;
    }

    private function testRelationValues(array $relations): array
    {
        $error = false;
        $message = '';
        if (!isset($relations['entity'])) {
            $error = true;
            $message = 'missing relation entity';
        } else if ('' === $relations['entity']) {
            $message = 'missing relation entity';
            $error = true;
        }
        if (!isset($relations['key'])) {
            $error = true;
            $message = 'missing relation key';
        } else if ('' === $relations['key']) {
            $error = true;
            $message = 'missing relation key';
        }

        if (!in_array(strtoupper($relations['onDelete']), $this->authorisedConstraints)) {
            $error = true;
            $message = 'OnDelete not supported type';
        }

        if (!in_array(strtoupper($relations['onUpdate']), $this->authorisedConstraints)) {
            $error = true;
            $message = 'onUpdate not supported type';
        }
        return [
            'error' => $error,
            'message' => $message
        ];
    }

    private function makeIndex(string $name, array $relations): string
    {
        $entityRelationName = $relations['entity'];
        $columnName = $relations['key'];
        $indexName = "fk_{$columnName}_{$entityRelationName}";
        $this->indexes[$indexName] = [
            'unique' => false,
            'columns' => $columnName
        ];
        return $columnName;
    }
}

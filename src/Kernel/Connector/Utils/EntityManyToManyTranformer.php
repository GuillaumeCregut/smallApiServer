<?php

namespace App\Kernel\Connector\Utils;

use InvalidArgumentException;

class EntityManyToManyTranformer
{
    private array $doneTable = [];
    public function transform(array $entities): array
    {
        $returnArray = [];
        $this->doneTable = [];
        foreach ($entities as $entity => $properties) {
            $relationProperties = $this->FormatProperties($entity, $properties);
            $returnArray = array_merge($returnArray, $relationProperties);
        }
        return $returnArray;
    }

    private function FormatProperties(string $entityName, array $properties): array
    {
        $returnArray = [];
        foreach ($properties as $property => $relation) {
            $analyse = $this->checkRelation($relation);
            if ($analyse['error']) {
                $message = sprintf($analyse['message'], $entityName, $property);
                throw new InvalidArgumentException($message);
            }
            if (in_array($relation['tableName'], $this->doneTable)) {
                continue;
            }
            $this->doneTable[] = $relation['tableName'];
            $returnArray[] = [
                'pivotTable' => $relation['tableName'],
                'ownerTable' => $relation['tableOwner'],
                'targetTable' => $relation['tableTarget'],
                'ownerCol' => $relation['colOwner'],
                'targetCol' => $relation['colTarget']
            ];
        }
        return $returnArray;
    }

    private function checkRelation(array $relation): array
    {
        $error = false;
        $message = '';
        if (!isset($relation['tableName'])) {
            return [
                'error' => true,
                'message' => 'Entity %s property %s missing tableName'
            ];
        }

        if (!isset($relation['colOwner'])) {
            return [
                'error' => true,
                'message' => 'Entity %s property %s missing colOwner'
            ];
        }

        if (!isset($relation['colTarget'])) {
            return [
                'error' => true,
                'message' => 'Entity %s property %s missing colTarget'
            ];
        }

        if (!isset($relation['tableOwner'])) {
            return [
                'error' => true,
                'message' => 'Entity %s property %s missing tableOwner'
            ];
        }

        if (!isset($relation['tableTarget'])) {
            return [
                'error' => true,
                'message' => 'Entity %s property %s missing tableTarget'
            ];
        }
        return [
            'error' => $error,
            'message' => $message
        ];
    }
}
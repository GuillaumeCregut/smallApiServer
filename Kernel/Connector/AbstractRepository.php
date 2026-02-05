<?php

namespace App\Kernel\Connector;


use App\Kernel\Interfaces\Databases\ConnectorInterface;
use App\Kernel\Interfaces\Databases\EntityInterface;
use App\Kernel\Interfaces\Databases\RepositoryInterface;

abstract class AbstractRepository implements RepositoryInterface
{
    protected ConnectorInterface $connector;
    protected EntityInterface $entity;

    abstract function insert(EntityInterface $entity): EntityInterface;
    abstract function update(EntityInterface $entity): EntityInterface;


    public function __construct()
    {
        $this->connector = ConnectorDispatcher::getConnector();
    }

    public function save(EntityInterface $entity): EntityInterface
    {
        if (null === $entity->getId()) {
            $result = $this->insert($entity);
        } else {
            $result = $this->update($entity);
        }
        return $result;
    }

    // Entity: $firstName
    // Database: first_name
    protected function propertyToColumn(string $property): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $property));
    }

    protected function columnToProperty(string $column): string
    {
        // user_name → userName
        // first_name → firstName
        // id → id 
        return lcfirst(str_replace('_', '', ucwords($column, '_')));
    }
}

<?php

namespace App\Kernel\Connector;


use App\Kernel\Interfaces\Databases\ConnectorInterface;
use App\Kernel\Interfaces\Databases\EntityInterface;
use App\Kernel\Interfaces\Databases\RepositoryInterface;

abstract class AbstractRepository implements RepositoryInterface
{
    protected ConnectorInterface $connector;
    protected ?string $entity = null;

    abstract public function insert(EntityInterface $entity): EntityInterface;
    abstract public function update(EntityInterface $entity): EntityInterface;
    abstract function find(int $id): EntityInterface;
    abstract public function findBy(array $fields): array;
    abstract public function findAll(): array;
    abstract protected function deleteEntity(EntityInterface $entity): void;
    public function __construct()
    {
        $this->connector = ConnectorDispatcher::getConnector();
        if (!is_subclass_of($this->entity, EntityInterface::class)) {
            throw new DatabaseException('Entity class must be an instance of EntityInterface');
        }
    }

    public function delete(EntityInterface $entity): void
    {
        $this->checkEntity($entity);
        $this->deleteEntity($entity);
    }

    public function save(EntityInterface $entity): EntityInterface
    {
        $this->checkEntity($entity);
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

    protected function checkEntity(EntityInterface $entity): void
    {
        $entityClass = get_class($entity);
        if($entityClass !== $this->entity) {
            throw new DatabaseException('Entity class must be an instance of ' . $this->entity);
        }
    }

   
}

<?php

namespace App\Kernel\Connector;

use App\Kernel\Connector\Attributes\NotStored;
use App\Kernel\Connector\Attributes\Nullable;
use App\Kernel\Interfaces\Databases\ConnectorInterface;
use App\Kernel\Interfaces\Databases\EntityInterface;
use App\Kernel\Interfaces\Databases\RepositoryInterface;
use Reflection;
use ReflectionClass;

abstract class AbstractRepository implements RepositoryInterface
{
    protected ConnectorInterface $connector;
    protected ?string $entity = null;
    protected ?string $entityTableName = null;
    protected array $entityProperties = [];
    protected ?ReflectionClass $reflectionEntity = null;

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


    public function createSqlTable(): string
    {
        if (null === $this->reflectionEntity) {
            $this->reflectionEntity = new ReflectionClass($this->entity);
        }
        $reflexionEntity = $this->reflectionEntity;
        $tableName = $this->getTableName();
        $properties = $this->getEntityProperties($reflexionEntity)['stored'];
        $pk= "";
        $sql = "CREATE TABLE $tableName (";
        if(!array_key_exists('id',$properties)) {
           $sql .= "id INT NOT NULL AUTO_INCREMENT, ";
            $pk = "PRIMARY KEY (id)";
        }
        foreach($properties as $name => $config) {
            if ('id' === $name) {
                $partSql = "{$name} INT NOT NULL AUTO_INCREMENT, ";
                $pk = "PRIMARY KEY (id)";
            } else {
                $isNull = $config['nullable'] ? 'NULL' : 'NOT NULL';
                $sqlName = $this->propertyToColumn($name);
                $sqlType = $this->returnSqlType($config['type']);
                $partSql = "{$sqlName} {$sqlType} {$isNull}";
            }
            $sql .= $partSql . ", ";
        }
        $sql .= "{$pk})";
        return $sql;
    }

    public function getTableName(): string
    {
        if (null === $this->entityTableName) {
            if (null === $this->reflectionEntity) {
                $this->reflectionEntity = new ReflectionClass($this->entity);
            }
            $tableName =  $this->reflectionEntity->getShortName();
            $newName = preg_replace('/entity$/i', '', $tableName);
            $tableName = $this->propertyToColumn($newName);
            $this->entityTableName = $tableName;
        }
        return $this->entityTableName;
    }

    protected function getEntityProperties(ReflectionClass $class): array
    {
        if (empty($this->entityProperties)) {
            $stored = [];
            $unStored = [];
            $listProperties = $class->getProperties();
            foreach ($listProperties as $property) {
                $attribute = $property->getAttributes(NotStored::class);
                $nullable = $property->getAttributes(Nullable::class);
                $typeProperty = $property->getType()->getName();
                $nameProperty = $property->getName();
                if (null !== $nullable && count($nullable) > 0) {
                    $nullable = true;
                } else {
                    $nullable = false;
                }
                $arrayProperty = [
                   'type' => $typeProperty,
                   'nullable' => $nullable
                ];

                if (null !== $attribute && count($attribute) > 0) {
                    //Unstored value
                    $unStored[$nameProperty] = $arrayProperty;
                } else {
                    //Stored value
                    $stored[$nameProperty] = $arrayProperty;
                }
            }
            $this->entityProperties = [
                'stored' => $stored,
                'unStored' => $unStored
            ];
        }
        return $this->entityProperties;
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
        if ($entityClass !== $this->entity) {
            throw new DatabaseException('Entity class must be an instance of ' . $this->entity);
        }
    }

    protected function returnSqlType(string $type): string
    {
        $types = [
            'string' => 'VARCHAR(255)',
            'int' => 'INT',
            'DateTimeImmutable' => 'DATETIME', 
            'DateTime' => 'DATETIME', 
            'float' =>'FLOAT',
            'bool' => 'BOOLEAN',
            'array' => 'JSON'
        ];
        if(array_key_exists($type, $types)){
            return $types[$type];
        } 
        throw new DatabaseException('Type can not be converted into SQL type');
    }
}

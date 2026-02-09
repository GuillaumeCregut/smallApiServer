<?php

namespace App\Kernel\Connector;

use App\Kernel\Connector\Attributes\NotStored;
use App\Kernel\Connector\Attributes\Nullable;
use App\Kernel\Interfaces\Databases\ConnectorInterface;
use App\Kernel\Interfaces\Databases\EntityInterface;
use App\Kernel\Interfaces\Databases\RepositoryInterface;
use ReflectionClass;

abstract class AbstractRepository implements RepositoryInterface
{
    public string $sql = ''; //This is intented for tests.
    protected ConnectorInterface $connector;
    protected ?string $entity = null;
    protected ?string $entityTableName = null;
    protected array $entityProperties = [];
    protected ?ReflectionClass $reflectionEntity = null;
    protected ?string $entityName = null;
    protected QueryBuilder $qb;

    public function __construct()
    {
        $this->connector = ConnectorDispatcher::getConnector();
        if (!is_subclass_of($this->entity, EntityInterface::class)) {
            throw new DatabaseException('Entity class must be an instance of EntityInterface');
        }
        $table = $this->getTableName();
        if (null === $table) {
            throw new DatabaseException('Entity class must be an instance of EntityInterface');
        }
        $this->qb = new QueryBuilder($table);
    }

    public function find(int $id): ?EntityInterface
    {
        $query = $this->qb->where('id', '=', $id)->toSql();
        $this->sql = $query;
        $params = $this->qb->getParams();
        $result = $this->sendQuery(true, $query, $params);
        if (0 === count($result)) {
            return null;
        }
        if (1 < count($result)) {
            throw new DatabaseException('Many results are found');
        }
        $entity = $this->makeEntity($result[0]);
        return $entity;
    }

    public function findBy(array $fields): array
    {
        foreach ($fields as $key => $value) {

            $query = $this->qb->where($key, '=', $value);
        }
        $query = $this->qb->toSql();
        $this->sql = $query;
        $result = $this->sendQuery(true, $query, []);
        if (0 === count($result)) {
            return [];
        }
        $returnArray = [];
        foreach ($result as $values) {
            $entity = $this->makeEntity($values);
            $returnArray[] = $entity;
        }
        return $returnArray;
        return [];
    }

    public function findAll(): array
    {
        $query = $this->qb->toSql();
        $this->sql = $query;
        $result = $this->sendQuery(true, $query, []);
        if (0 === count($result)) {
            return [];
        }
        $returnArray = [];
        foreach ($result as $values) {
            $entity = $this->makeEntity($values);
            $returnArray[] = $entity;
        }
        return $returnArray;
    }

    public function delete(EntityInterface $entity): void
    {
        $this->checkEntity($entity);
        //TODO create function
    }

    public function save(EntityInterface $entity): ?EntityInterface
    {
        $this->checkEntity($entity);
        if (null === $this->reflectionEntity) {
            $this->reflectionEntity = new ReflectionClass($this->entity);
        }
        $this->getEntityProperties($this->reflectionEntity);
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
        $pk = "";
        $sql = "CREATE TABLE $tableName (";
        if (!array_key_exists('id', $properties)) {
            $sql .= "id INT NOT NULL AUTO_INCREMENT, ";
            $pk = "PRIMARY KEY (id)";
        }
        foreach ($properties as $name => $config) {
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
            $this->entityName = $newName;
            $tableName = $this->propertyToColumn($newName);
            $this->entityTableName = $tableName;
        }
        return $this->entityTableName;
    }

    protected function insert(EntityInterface $entity): ?EntityInterface
    {
        //TODO make function
        $EntityValues = $this->getEntityValues($entity);
        $columns = [];
        $values = [];
        foreach ($EntityValues as $key => $value) {
            if(null === $value) {
                continue;
            }
            $columns[] = $key;
            $values[] = $value;
        }
        $query = $this->qb->insert($columns)
        ->values($values)
        ->toSql();
        $params = $this->qb->getParams();
        $this->sql = $query;
        $result = $this->sendQuery(false, $query, $params);
        if(!$result) {
            return null;
        }
        $entity->setId($result);
        return $entity;
    }

    protected  function update(EntityInterface $entity): EntityInterface
    {
        return $entity;
        //TODO : create function
    }

    protected function getEntityValues(EntityInterface $entity): array
    {
        $storedValues = $this->entityProperties['stored'];
        $returnArray = [];
        foreach($storedValues as $column => $type){
            $getFunction = 'get' . ucfirst($column);
            $value = $entity->$getFunction();
            $returnArray[$column] = $value;
        }
        return $returnArray;
    }

    protected function makeEntity(array $values): EntityInterface
    {
        $newRow = [];
        foreach ($values as $attribute => $value) {
            $key = $this->columnToProperty($attribute);
            $newRow[$key] = $value;
        }
        return Hydrator::hydrate(new $this->entity(), $newRow);
    }

    protected function sendQuery(bool $isSelect, string $query, array $params): array | bool | int
    {
        try {
            if ($isSelect) {
                $result = $this->connector->fetchQuery($query, $params);
            } else {
                $result = $this->connector->executeQuery($query, $params);
            }
            return $result;
        } catch (DatabaseException $e) {
            $name = get_class($this);
            throw new DatabaseException("Repository {$name} failed whith query : '{$query}'", $e->getCode());
        }
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
            'float' => 'FLOAT',
            'bool' => 'BOOLEAN',
            'array' => 'JSON'
        ];
        if (array_key_exists($type, $types)) {
            return $types[$type];
        }
        throw new DatabaseException('Type can not be converted into SQL type');
    }
}

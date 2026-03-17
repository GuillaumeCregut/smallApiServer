<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector;

use Error;
use Exception;
use ReflectionClass;
use App\Kernel\Connector\Hydrator;
use App\Kernel\Connector\QueryBuilder;
use App\Kernel\Connector\Datas\LazyBag;
use App\Kernel\Psr14\Events\PostFindEvent;
use App\Kernel\Connector\DatabaseException;
use App\Kernel\Psr14\Events\PreRemoveEvent;
use App\Kernel\Psr14\Events\PreUpdateEvent;
use App\Kernel\Psr14\Events\PostRemoveEvent;
use App\Kernel\Psr14\Events\PostUpdateEvent;
use App\Kernel\Psr14\Events\PrePersistEvent;
use App\Kernel\Connector\Attributes\Nullable;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Psr14\Events\PostPersistEvent;
use App\Kernel\Connector\Attributes\ManyToOne;
use App\Kernel\Connector\Attributes\NotStored;
use App\Kernel\Connector\Attributes\OneToMany;
use App\Kernel\Psr14\Listener\ListenerProvider;
use App\Kernel\Psr14\Dispatcher\EventDispatcher;
use App\Kernel\Connector\Interfaces\EntityInterface;
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Connector\Interfaces\RepositoryInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;
use App\Kernel\Connector\Interfaces\EntityManagerInterface;
use App\Kernel\Connector\Utils\Helper;
use App\Kernel\Files\FileUpload;

abstract class AbstractRepository implements RepositoryInterface
{
    public string $sql = ''; //This is intented for tests.
    public array $params = []; //This is intented for tests.
    protected ConnectorInterface $connector;
    protected ?string $entity = null;
    protected ?string $entityTableName = null;
    protected array $entityProperties = [];
    protected ?ReflectionClass $reflectionEntity = null;
    protected ?string $entityName = null;
    protected QueryBuilder $qb;
    protected array $relations = [];
    protected ?EntityManagerInterface $em = null;

    public function __construct(?EntityManagerInterface $em = null)
    {
        $this->em = $em;
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

    public function setEntityManager(EntityManagerInterface $em): void
    {
        $this->em = $em;
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
        $this->qb->reset();
        return $entity;
    }

    public function findBy(array $fields): array
    {
        $key = array_key_first($fields);
        $query = $this->qb->where($key, '=', $fields[$key]);
        $fields = array_slice($fields, 1);
        foreach ($fields as $key => $value) {
            $query = $this->qb->andWhere($key, '=', $value);
        }
        $query = $this->qb->toSql();
        $params = $this->qb->getParams();
        $this->sql = $query;
        $result = $this->sendQuery(true, $query, $params);
        if (0 === count($result)) {
            return [];
        }
        $returnArray = [];
        foreach ($result as $values) {
            if (!is_array($values)) {
                throw new DatabaseException('missformed query result');
            }
            $entity = $this->makeEntity($values);
            $returnArray[] = $entity;
        }
        $this->qb->reset();
        return $returnArray;
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
        $this->qb->reset();
        return $returnArray;
    }

    public function delete(EntityInterface $entity): bool
    {
        $this->checkEntity($entity);
        $this->dispatch(new PreRemoveEvent($entity));
        $id = $entity->getId();
        $query = $this->qb->delete($id)
            ->where('id', '=', $id)
            ->toSql();
        $this->sql = $query;
        $params = $this->qb->getParams();
        $this->qb->reset();
        $result =  $this->sendQuery(false, $query, $params);
        if($result) {
            $this->dispatch(new PostRemoveEvent($entity));
        }
        return $result;
    }

    public function save(EntityInterface $entity): null | false | EntityInterface
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
                $partSql = "{$name} INT NOT NULL AUTO_INCREMENT ";
                $pk = "PRIMARY KEY (id)";
            } else {
                $isNull = $config['nullable'] ? 'NULL' : 'NOT NULL';
                $sqlName = Helper::propertyToColumn($name);
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
            $tableName = Helper::propertyToColumn($newName);
            $this->entityTableName = $tableName . 's';
        }
        return $this->entityTableName;
    }

    public function getRelations(): array
    {
        $resultArray = [];
        foreach ($this->relations as $key => $relation) {
            $name = Helper::propertyToColumn($key);
            $entity = $relation['relation']->targetEntity;
            $onDelete = strtoupper($relation['relation']->onDelete);
            $onUpdate = strtoupper($relation['relation']->onUpdate);
            $repoName = $entity::getRepository();
            $repo = new $repoName();
            $foreignTable = $repo->getTableName();
            $result = [
                $this->getTableName(),
                $name,
                $foreignTable
            ];
            $id = strtoupper(bin2hex(random_bytes(8)));
            $constraintName = "FK_{$id}";
            $sql = "ALTER TABLE {$this->getTableName()} ADD CONSTRAINTS {$constraintName} FOREIGN KEY ({$name}) REFERENCES {$foreignTable} (id) ON DELETE {$onDelete} ON UPDATE {$onUpdate}";
            $resultArray[] = $sql;
        }
        return $resultArray;
    }
    protected function insert(EntityInterface $entity): ?EntityInterface
    {
        $this->dispatch(new PrePersistEvent($entity));
        $entityValues = $this->getEntityValues($entity);
        $columns = [];
        $values = [];
        foreach ($entityValues as $key => $value) {
            if (null === $value) {
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
        foreach ($params as $key => $param) {
            if ($param instanceof EntityInterface) {
                $params[$key] = $param->getId();
            }
            if($param instanceof FileUpload) {
                 $params[$key] = $param->getFullPath();
            }
        }
        $this->params = $params;
        $result = $this->sendQuery(false, $query, $params);
        if (!$result) {
            return null;
        }
        $entity->setId($result);
        $this->qb->reset();
        $this->dispatch(new PostPersistEvent($entity));
        return $entity;
    }

    protected  function update(EntityInterface $entity): EntityInterface | false
    {
        $this->dispatch(new PreUpdateEvent($entity));
        $entityValues = $this->getEntityValues($entity);
        $id = $entity->getId();
        if (isset($entityValues['id'])) {
            unset($entityValues['id']);
        }
        $query = $this->qb->update($entityValues)
            ->where('id', '=', $id)
            ->toSql();
        $params = $this->qb->getParams();
        foreach ($params as $key => $param) {
            if ($param instanceof EntityInterface) {
                $params[$key] = $param->getId();
            }
            if($param instanceof FileUpload) {
                 $params[$key] = $param->getFullPath();
            }
        }
        $this->params = $params;
        $this->sql = $query;
        $result = $this->sendQuery(false, $query, $params);
        $this->qb->reset();
        if (false === $result) {
            return false;
        }
        $this->dispatch(new PostUpdateEvent($entity));
        return $entity;
    }

    protected function getEntityValues(EntityInterface $entity): array
    {
        $storedValues = $this->entityProperties['stored'];
        $returnArray = [];
        foreach ($storedValues as $column => $type) {
            $columnName = $column;
            if (isset($type['relation'])) {
                if (str_ends_with($column, 'Id')) {
                    $columnName = substr($column, 0, -2);
                } else {
                    throw new DatabaseException("Error finding column name {$column} in {$this->entityName}");
                }
            }
            $getFunction = 'get' . ucfirst($columnName);
            $value = $entity->$getFunction();
            $returnArray[$column] = $value;
        }
        return $returnArray;
    }

    protected function getRelationInDb(): array
    {
        $properties = $this->entityProperties['stored'];
        $returnArray = [];
        foreach ($properties as $key => $property) {
            if (isset($property['relation'])) {
                $returnArray[$key] = $property['relation'];
            }
        }
        return $returnArray;
    }

    protected function makeEntity(array $values): EntityInterface
    {
        $newRow = [];
        foreach ($values as $attribute => $value) {
            $key = Helper::columnToProperty($attribute);
            $newValue = $this->checkIncomingValue($key, $value);
            $newRow[$key] = $newValue;
        }
        $entity = Hydrator::hydrate(new $this->entity(), $newRow);
        $relations = $this->getRelationInDb();
        try {
            foreach ($relations as $name => $params) {
                if (!isset($newRow[$name])) {
                    throw new DatabaseException("Error finding in {$this->entityName} relation called {$name}");
                }
                $idRelation = $newRow[$name];
                $targetEntity = $params->targetEntity;
                $newName = substr($name, 0, -2);
                if (!str_ends_with($name, 'Id')) {
                    throw new DatabaseException("Error in {$this->entityName} finding name for {$newName}");
                }
                $entityRepo = $targetEntity::getRepository();
                $newRepoRelation = new $entityRepo();
                if (null !== $this->em) {
                    $newEntityRelation = $this->em->find($targetEntity, $idRelation);
                } else {
                    $newEntityRelation = $newRepoRelation->find($idRelation);
                }
                $setter = 'set' . ucfirst($newName);
                if (!method_exists($entity, $setter)) {
                    throw new DatabaseException("No setter found for {$newName} in {$this->entityName}");
                }
                $entity->$setter($newEntityRelation);
            }
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage());
        } catch (Error $e) {
            throw new DatabaseException($e->getMessage());
        }

        $relations = $this->entityProperties['unStored'];
        foreach ($relations as $propertyName => $config) {
            if ('relation' !== $config['type']) {
                continue;
            }
            $relation = $config['relation'];
            /**@var OneToMany $relation */
            $targetEntity = $relation->targetEntity;
            /**@var EntityInterface $entity */
            $targetRepoName = $targetEntity::getRepository();
            $id = $entity->getid();
            $em = $this->em;
            $bag = new LazyBag(function () use ($id, $targetRepoName, $relation, $em): array {
                $targetRepo = new $targetRepoName();
                if (null !== $em) {
                    $targetRepo->setEntityManager($em);
                }
                $results = $targetRepo->findBy([
                    $relation->mappedBy . '_id' => $id
                ]);
                if (null !== $em) {
                    return array_map(
                        fn(EntityInterface $e) => $em->getIdentityMap()->getOrRegister($e),
                        $results
                    );
                }
                return $results;
            });
            $setter = 'set' . ucfirst($propertyName);
            $entity->$setter($bag);
        }
        $this->dispatch(new PostFindEvent($entity));
        return $entity;
    }

    protected function  checkIncomingValue(string $name, mixed $value): mixed
    {
        $properties = $this->getEntityProperties($this->reflectionEntity)['stored'];
        if (key_exists($name, $properties)) {
            if ($properties[$name]['type'] == 'array') {
                $value = json_decode($value, true);
            }
            if ($properties[$name]['type'] == 'file') {
                $value = FileUpload::fromPath($value);
            }
        }
        return $value;
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
                $oneToMany = $property->getAttributes(OneToMany::class);
                $manyToOne = $property->getAttributes(ManyToOne::class);
                //Check if type is Buidtin
                $typeProperty = $property->getType()->getName();
                if(!$property->getType()->isBuiltin()) {
                    if(FileUpload::class !== $typeProperty &&
                      LazyBag::class !== $typeProperty &&
                      !is_a($typeProperty,EntityInterface::class, true)) {
                        throw new DatabaseException("Object {$typeProperty} can't be stored in database");
                    }
                    $typeProperty = "file";
                }
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

                if ((null !== $attribute && count($attribute) > 0) || (null !== $oneToMany && count($oneToMany) > 0)) {
                    if (!empty($oneToMany)) {
                        $arrayProperty['relation'] = $oneToMany[0]->newInstance();
                        $arrayProperty['type'] = 'relation';
                    }
                    $unStored[$nameProperty] = $arrayProperty;
                } else {
                    if ((null !== $manyToOne) && (!empty($manyToOne))) {
                        $arrayProperty['relation'] = $manyToOne[0]->newInstance();
                        $arrayProperty['type'] = 'int';
                        $nameProperty = $nameProperty . 'Id';
                        $this->relations[$nameProperty] = $arrayProperty;
                    }
                    $stored[$nameProperty] = $arrayProperty;
                }
            }
            $stored = $this->ensureIdFirst($stored);
            $this->entityProperties = [
                'stored' => $stored,
                'unStored' => $unStored
            ];
        }
        return $this->entityProperties;
    }

    protected function ensureIdFirst(array $array): array
    {
        if (!array_key_exists('id', $array)) {
            return $array;
        }

        return ['id' => $array['id']] + array_diff_key($array, ['id' => null]);
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
            'array' => 'JSON',
            'file' => 'VARCHAR(255)'
        ];
        if (array_key_exists($type, $types)) {
            return $types[$type];
        }
        throw new DatabaseException("Type {$type}  can not be converted into SQL type");
    }

    private function dispatch(StoppableEventInterface $event): void
    {
        $provider = ListenerProvider::getInstance();

        //Launch initKernelEvent
        EventDispatcher::getInstance($provider)->dispatch($event);
    }
}

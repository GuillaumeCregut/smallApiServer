<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Management;

use App\Kernel\Connector\Attributes\ManyToMany;
use Throwable;
use ReflectionClass;
use App\Kernel\Connector\DatabaseException;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Connector\Datas\LazyBag;
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Connector\Interfaces\EntityInterface;
use App\Kernel\Connector\Interfaces\RepositoryInterface;
use App\Kernel\Connector\Interfaces\IdentityMapInterface;
use App\Kernel\Connector\Interfaces\EntityManagerInterface;

/**
 * Lightweight EntityManager.
 *
 * Responsibilities:
 *  - Identity map: one PHP object per (class, id) per request
 *  - Unit of Work : track new / dirty / removed entities
 *  - Flush        : persist everything in a single transaction,
 *                   ManyToOne targets saved before their owners
 */
class EntityManager implements EntityManagerInterface
{
    private IdentityMapInterface $identityMap;
    private static ?EntityManagerInterface $instance =null;
    /** Entities to insert (id === null when persisted) */
    private array $new     = [];

    /** Entities to update (id set, already in DB) */
    private array $dirty   = [];

    /** Entities to delete */
    private array $removed = [];

    private function __construct(?IdentityMapInterface $identityMap = null)
    {
        $this->identityMap = $identityMap ?? new IdentityMap();
    }

    public static function getInstance(?IdentityMapInterface $identityMap): EntityManagerInterface
    {
        if(null === self::$instance) {
            self::$instance = new EntityManager($identityMap);
        }
        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public function find(string $class, int $id): ?EntityInterface
    {
        $existing = $this->identityMap->get($class, $id);
        if (null !== $existing) {
            return $existing;
        }
        $repoClass = $class::getRepository();
        $repo      = new $repoClass($this);
        $entity    = $repo->find($id);
        if (null !== $entity) {
            $entity = $this->identityMap->getOrRegister($entity);
        }
        return $entity;
    }

    public function findBy(string $class, array $criteria): array
    {
        $repoClass = $class::getRepository();
        $repo      = new $repoClass($this);
        $results   = $repo->findBy($criteria);

        return array_map(
            fn(EntityInterface $e) => $this->identityMap->getOrRegister($e),
            $results
        );
    }

    public function findAll(string $class): array
    {
        $repoClass = $class::getRepository();
        $repo      = new $repoClass($this);
        $results   = $repo->findAll();

        return array_map(
            fn(EntityInterface $e) => $this->identityMap->getOrRegister($e),
            $results
        );
    }

    public function persist(EntityInterface $entity): void
    {
        if (in_array($entity, $this->new, true) || in_array($entity, $this->dirty, true)) {
            return;
        }

        if (null === $entity->getId()) {
            $this->new[] = $entity;
        } else {
            $this->dirty[] = $entity;
            $this->identityMap->getOrRegister($entity);
        }
    }

    public function remove(EntityInterface $entity): void
    {
        if (in_array($entity, $this->removed, true)) {
            return;
        }
        $this->new   = array_filter($this->new,   fn($e) => $e !== $entity);
        $this->dirty = array_filter($this->dirty, fn($e) => $e !== $entity);
        $this->removed[] = $entity;
    }

    /**
     * Persist all pending changes inside a single transaction.
     *
     * Insert order: entities with no unresolved FK first.
     * A simple two-pass approach handles one level of ManyToOne dependency
     * without needing a full topological sort.
     */
    public function flush(): void
    {
        $connector = ConnectorDispatcher::getConnector();

        try {
            $connector->startTransac();
            $ordered = $this->orderByDependency($this->new);
            foreach ($ordered as $entity) {
                $repo = $this->getRepository($entity);
                $repo->save($entity);
                // Now that it has an id, register it.
                $this->identityMap->getOrRegister($entity);
                $this->syncManyToManyPivots($entity, $connector);
            }

            foreach ($this->dirty as $entity) {
                $repo = $this->getRepository($entity);
                $repo->save($entity);
                $this->syncManyToManyPivots($entity, $connector);
            }

            // --- Deletes ---
            foreach ($this->removed as $entity) {
                $repo = $this->getRepository($entity);
                $repo->delete($entity);
                $this->identityMap->detach($entity);
            }
            $connector->commitTransac();
        } catch (Throwable $e) {
            $connector->rollBack();
            throw new DatabaseException('EntityManager::flush() failed: ' . $e->getMessage(), (int) $e->getCode());
        }
        $this->reset();
    }

    public function getIdentityMap(): IdentityMapInterface
    {
        return $this->identityMap;
    }

    public function isManaged(EntityInterface $entity): bool
    {
        return in_array($entity, $this->new, true)
            || in_array($entity, $this->dirty, true);
    }

    public function isNew(EntityInterface $entity): bool
    {
        return in_array($entity, $this->new, true);
    }

    public function detach(EntityInterface $entity): void
    {
        $this->new     = array_values(array_filter($this->new,     fn($e) => $e !== $entity));
        $this->dirty   = array_values(array_filter($this->dirty,   fn($e) => $e !== $entity));
        $this->removed = array_values(array_filter($this->removed, fn($e) => $e !== $entity));
        $this->identityMap->detach($entity);
    }

    public function clear(): void
    {
        $this->reset();
        $this->identityMap->clear();
    }

    /**
     * Convenience wrapper: runs $fn inside a transaction.
     * Rolls back and rethrows on any exception.
     *
     *   $em->transactional(function() use ($em, $order) {
     *       $em->persist($order);
     *       $em->flush();
     *   });
     */
    public function transactional(callable $fn): void
    {
        $connector = ConnectorDispatcher::getConnector();
        try {
            $connector->startTransac();
            $fn();
            $connector->commitTransac();
        } catch (Throwable $e) {
            $connector->rollBack();
            throw new DatabaseException('Transaction failed: ' . $e->getMessage(), (int) $e->getCode());
        }
    }

    private function syncManyToManyPivots(EntityInterface $entity, ConnectorInterface $connector): void
    {
        $class = get_class($entity);
        $repoClass = $class::getRepository();
        $repo = new $repoClass();
        $ownerTable = $repo->getTableName();
        $ownerId = $entity->getId();
        $pivotMgr = new PivotTableManager($connector);
        $reflection = new ReflectionClass($class);

        foreach($reflection->getProperties() as $property) {
            $attrs = $property->getAttributes(ManyToMany::class);
            if(empty($attrs)) {
                continue;
            }
            $relation = $attrs[0]->newInstance();

            //Skip inverse side
            if('' !== $relation->mappedBy) {
                continue;
            }

            /**@var ManyToMany $relation */
            $targetEntity = $relation->targetEntity;
            $targetRepoName = $targetEntity::getRepository();
            $targetRepo = new $targetRepoName();
            $targetTable = $targetRepo->getTableName();
            $pivotTable = $pivotMgr->getTableName($ownerTable, $targetTable, $relation);
            
            $ownerCol = $relation->ownerColumn; 
            $targetCol = $relation->targetColumn; 
            /**@var LazyBag $bag */
            $bag = $property->getValue($entity);
            if(!($bag instanceof LazyBag) || !$bag->isDirty()) {
                continue;
            }
            $currentIds = array_map(
                fn(EntityInterface $e) => $e->getId(), 
                $bag->toArray()
                );
            $currentIds = array_filter($currentIds, fn($id) => null !== $id);
            $pivotMgr->sync($pivotTable, $ownerCol, $targetCol, $ownerId, array_values($currentIds));
        }
    }

    private function getRepository(EntityInterface $entity): RepositoryInterface
    {
        $class     = get_class($entity);
        $repoClass = $class::getRepository();
        return new $repoClass($this);
    }

    /**
     * Order new entities so that ManyToOne targets come before their owners.
     *
     * Strategy: entities whose ManyToOne relations all have an id already
     * (either pre-existing or already inserted this flush) go first.
     * Two passes cover the common case of a single FK level.
     * Deeper chains will be handled by subsequent passes up to a max of
     * count($entities) iterations (cycle detection fallback).
     */

    private function orderByDependency(array $entities): array
    {
        $ordered  = [];
        $inserted = []; // track spl_object_id of already-ordered entities
        $remaining = $entities;
        $maxPasses = count($entities) + 1;
        $pass = 0;

        while (!empty($remaining) && $pass < $maxPasses) {
            $pass++;
            $stillRemaining = [];

            foreach ($remaining as $entity) {
                if ($this->dependenciesResolved($entity, $inserted)) {
                    $ordered[]  = $entity;
                    $inserted[spl_object_id($entity)] = true;
                } else {
                    $stillRemaining[] = $entity;
                }
            }

            // No progress — circular or unresolvable dependency, flush as-is.
            if (count($stillRemaining) === count($remaining)) {
                $ordered = array_merge($ordered, $stillRemaining);
                break;
            }

            $remaining = $stillRemaining;
        }

        return $ordered;
    }

    /**
     * Check that every ManyToOne relation on $entity either:
     *  - is null (optional FK)
     *  - already has an id (pre-existing in DB)
     *  - is in the $insertedIds set (just inserted this flush)
     */
    private function dependenciesResolved(EntityInterface $entity, array $insertedIds): bool
    {
        $class = get_class($entity);
        $repoClass = $class::getRepository();
        /** @var AbstractRepository $repo */
        $repo = new $repoClass();
        $repo->getTableName(); 

        $reflection = new ReflectionClass($class);
        foreach ($reflection->getProperties() as $property) {
            $manyToOne = $property->getAttributes(\App\Kernel\Connector\Attributes\ManyToOne::class);
            if (empty($manyToOne)) {
                continue;
            }
            $related = $property->getValue($entity);
            if (null === $related) {
                continue; // nullable FK, fine
            }
            if (null !== $related->getId()) {
                continue; // already in DB
            }
            // Related entity has no id yet — check if we just inserted it.
            if (!isset($insertedIds[spl_object_id($related)])) {
                return false;
            }
        }

        return true;
    }
    private function reset(): void
    {
        $this->new     = [];
        $this->dirty   = [];
        $this->removed = [];
    }
}

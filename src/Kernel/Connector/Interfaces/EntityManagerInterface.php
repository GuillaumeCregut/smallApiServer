<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Interfaces;

interface EntityManagerInterface
{
    /**
     * Find an entity by class and id.
     * Returns the identity-map instance if already loaded,
     * otherwise fetches from DB, registers, and returns it.
     */
    public function find(string $class, int $id): ?EntityInterface;

    /**
     * Find entities matching criteria.
     * Every result is passed through the identity map.
     */
    public function findBy(string $class, array $criteria): array;

    /**
     * Find all entities of a given class.
     * Every result is passed through the identity map.
     */
    public function findAll(string $class): array;

    /**
     * Expose the identity map so repositories can call getOrRegister()
     * from inside LazyBag loaders.
     */
    public function getIdentityMap(): IdentityMapInterface; //to be defined but return IdentityMap

    /**
     * Mark an entity for insert (id === null) or update (id set).
     * Does not touch the DB yet.
     */
    public function persist(EntityInterface $entity): void;

    /**
     * Execute all pending inserts, updates and deletes in a single transaction,
     * in dependency order (ManyToOne targets before owners).
     */
    public function flush(): void;

    /**
     * Mark an entity for deletion on next flush.
     */
    public function remove(EntityInterface $entity): void;
}
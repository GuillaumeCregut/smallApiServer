<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Interfaces;

interface IdentityMapInterface
{
    /**
     * Retrieve a tracked entity by class name and id.
     * Returns null if not yet registered.
     */
    public function get(string $class, int $id): ?EntityInterface;

    /**
     * Register an entity in the map.
     * If the same (class, id) is already registered, the existing
     * instance is kept and returned — the new one is discarded.
     */
    public function getOrRegister(EntityInterface $entity): EntityInterface;

    /**
     * Returns true if the entity is currently tracked.
     */
    public function has(EntityInterface $entity): bool;

    /**
     * Remove a specific entity from the map ( after deletion).
     */
    public function detach(EntityInterface $entity): void;

    /**
     *Clear the entire map (call at end of request / after flush).
     */
    public function clear(): void; 
}
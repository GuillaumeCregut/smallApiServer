<?php

namespace App\Kernel\Connector\Management;

use App\Kernel\Connector\Interfaces\EntityInterface;
use App\Kernel\Connector\Interfaces\IdentityMapInterface;

final class IdentityMap implements IdentityMapInterface
{
    /** @var array<string, array<int, EntityInterface>> */
    private array $map = [];

    /**
     * Return entity
     *
     * @param string $class
     * @param integer $id
     * @return EntityInterface|null
     */
    public function get(string $class, int $id): ?EntityInterface
    {
        return $this->map[$class][$id] ?? null;
    }

    /**
     * If (class, id) is already in the map, return the existing instance.
     * Otherwise register the incoming entity and return it.
     * This is the key method called by LazyBag loaders and EntityManager::find()
     * to ensure identity uniqueness across the whole request.
     *
     * @param EntityInterface $entity
     * @return EntityInterface
     */
    public function getOrRegister(EntityInterface $entity): EntityInterface
    {
        $class = get_class($entity);
        $id    = $entity->getId();
        // New entity not yet persisted — cannot key by id, just return as-is.
        if (null === $id) {
            return $entity;
        }
        if (isset($this->map[$class][$id])) {
            return $this->map[$class][$id];
        }
        $this->map[$class][$id] = $entity;
        return $entity;
    }

    public function has(EntityInterface $entity): bool
    {
        $class = get_class($entity);
        $id    = $entity->getId();

        if (null === $id) {
            return false;
        }

        return isset($this->map[$class][$id]);
    }

    public function detach(EntityInterface $entity): void
    {
        $class = get_class($entity);
        $id    = $entity->getId();

        if (null !== $id) {
            unset($this->map[$class][$id]);
        }
    }

    public function clear(): void
    {
        $this->map = [];
    }

}
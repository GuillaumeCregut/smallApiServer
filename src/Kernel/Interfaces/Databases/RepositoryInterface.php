<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Interfaces\Databases;

interface RepositoryInterface
{
 
    /**
     * find entities by id
     * @param int $id entity id
     * @return EntityInterface  
     */ 
    public function find(int $id): ?EntityInterface;
    
     /**
     * find entities by field
     * @param array $fields : name of field to search value
     * @return array<EntityInterface>
     */ 
    public function findBy(array $fields): array; 

    /**
     * Find all entities
     *
     * @return array<EntityInterface>
     */ 
    public function findAll(): array;

     /**
     * Save entity
     * @param EntityInterface entity
     * @return EntityInterface $new entity created
     */ 
    public function save(EntityInterface $entity): null | false | EntityInterface;

     /**
     * delete entity
     * @param EntityInterface entity
     *
     */ 
    public function delete(EntityInterface $entity): bool;

     public function createSqlTable(): string;

}
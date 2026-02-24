<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Management;

use App\Kernel\Connector\DatabaseException;
use App\Kernel\Connector\Interfaces\EntityInterface;
use App\Kernel\Connector\Interfaces\RepositoryInterface;

class RepositoryFactory
{
    public static function getRepository(string $entityClass): RepositoryInterface
    {
        if(!is_subclass_of($entityClass,EntityInterface::class)) {
            throw new DatabaseException('Entity must implements EntityInterface');
        }
        $repo = $entityClass::getRepository();
        if(null === $repo) {
            throw new DatabaseException('Entity must have valid Repository');
        }
        return new $repo();
    }
}
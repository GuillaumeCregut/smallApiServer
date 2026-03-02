<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector;

use App\Kernel\Connector\Interfaces\EntityInterface;

class Hydrator
{
    public static function hydrate(EntityInterface $entity, array $values): EntityInterface
    {
        foreach ($values as $row=>$value){
            $setter = 'set' . ucfirst($row);
            if(method_exists($entity, $setter)) {
                $entity->$setter($value);
            }
        }
        return $entity;
    }
}
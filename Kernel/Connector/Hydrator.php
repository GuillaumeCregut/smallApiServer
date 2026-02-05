<?php

namespace App\Kernel\Connector;

use App\Kernel\Interfaces\Databases\EntityInterface;

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
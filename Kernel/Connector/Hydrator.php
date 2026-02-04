<?php

namespace App\Kernel\Connector;

use App\Kernel\Interfaces\Databases\EntityInterface;

class Hydrator
{
    public static function hydrate(EntityInterface $entity, array $values): EntityInterface
    {
        foreach ($values as $row=>$value){
            $property=strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $row));
            $setter = 'set' . ucfirst($property);
            if(method_exists($entity, $setter)) {
                $entity->$setter($value);
            }
        }
        return $entity;
    }
}
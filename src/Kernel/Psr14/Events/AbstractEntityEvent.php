<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Psr14\Events;

use App\Kernel\Connector\Interfaces\EntityInterface;

abstract class AbstractEntityEvent extends AbstractStoppableEvent
{
    public function __construct(private EntityInterface $entity) {}

    public function getEntity(): EntityInterface
    {
        return $this->entity;
    }
}
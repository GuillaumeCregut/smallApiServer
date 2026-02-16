<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector;

use App\Kernel\Interfaces\Databases\EntityInterface;

abstract class AbstractEntity implements EntityInterface
{
    protected ?int $id = null;

    public function __construct()
    {
    }

    public function save(): self
    {
        //Todo : save entity
       // $this->repository->save($this);
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the value of id
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }
}
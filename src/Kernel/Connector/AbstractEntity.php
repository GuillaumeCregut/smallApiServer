<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector;

use App\Kernel\Connector\Attributes\NotStored;
use App\Kernel\Connector\Interfaces\EntityInterface;

abstract class AbstractEntity implements EntityInterface
{
    protected ?int $id = null;
    #[NotStored]
    protected ?string $repo = null;
    public function __construct()
    {
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getRepository(): ?string
    {
        return $this->repo;
    }
}
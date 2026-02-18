<?php

namespace App\Kernel\Interfaces\Databases;

interface EntityInterface
{
    public function getId(): ?int;
    public function setId(int $id): self;
}

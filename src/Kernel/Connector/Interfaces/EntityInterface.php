<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Interfaces;

interface EntityInterface
{
    public function getId(): ?int;
    public function setId(int $id): self;
    public static function getRepository(): ?string;
}

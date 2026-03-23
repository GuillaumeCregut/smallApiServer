<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Attributes;

use Attribute;

#[Attribute]
class ManyToMany
{
    public function __construct(
        public string $targetEntity,
        public string $ownerColumn,
        public string $targetColumn,
        public string $mappedBy ='', //Not for Owner
        public string $inversedBy= '', //Not for inversed Side
        public string $pivotTable = '',
    ) {}
}

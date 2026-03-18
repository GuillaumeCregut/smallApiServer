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
        public string $mappedBy ='',
        public string $inversedBy= '',
        public string $pivotTable = '',
    ) {}
}

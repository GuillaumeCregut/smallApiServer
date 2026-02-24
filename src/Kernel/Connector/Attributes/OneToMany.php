<?php

namespace App\Kernel\Connector\Attributes;

use \Attribute;

#[Attribute]
class OneToMany
{
    public function __construct(
        public string $targetEntity,
        public string $mappedBy,
    ) {}
}

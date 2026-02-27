<?php

namespace App\Kernel\Connector\Attributes;

use \Attribute;

#[Attribute]
class ManyToOne {
    public function __construct(
        public string $targetEntity,
        public string $inversedBy,
        public string $onDelete = "restrict",
        public string $onUpdate = "restrict"   
    ) {}
}
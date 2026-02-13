<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\utils;

class DumpLine
{
    public ?string $line;
    public mixed $value;
    public ?string $type;
    public ?string $name;

    public function __construct(?string $line, mixed $value, ?string $type, ?string $name)
    {
        $this->name = $name;
        $this->line = $line;
        $this->value = $value;
        $this->type = $type;
    }
}
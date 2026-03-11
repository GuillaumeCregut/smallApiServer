<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Form\Validator\Assert;

use \Attribute;

#[Attribute]
class NotNull extends AbstractAssert
{   
    public function __construct(?string $errorMessage = null)
    {
        $this->errorMessage = $errorMessage ?? 'Property %s must not be null';
    }
    final public function validate(mixed $value): bool
    {
        return $this->check($value);
    }

    protected function check(mixed $value): bool
    {
        return null !== $value;
    }
}
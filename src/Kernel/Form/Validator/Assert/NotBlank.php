<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Form\Validator\Assert;

use \Attribute;

#[Attribute]
class NotBlank extends AbstractAssert
{
    public function __construct(?string $errorMessage = null)
    {
        $this->errorMessage = $errorMessage ?? 'Value %s must not be blank';
    }

    public function validate(mixed $value): bool
    {
        if (!is_string($value)){
            return true;
        }
        $testValue = trim($value);
        if ('' === $testValue)  {
            return false;
        }
        return true;
    }
}

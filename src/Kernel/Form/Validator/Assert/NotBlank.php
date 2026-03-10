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
        $this->errorMessage = $errorMessage ?? 'property %s must not be blank';
        $this->allowedTypes = ['string'];
    }

    protected function check(mixed $value): bool
    {
        $testValue = trim($value);
        if ('' === $testValue)  {
            return false;
        }
        return true;
    }
}

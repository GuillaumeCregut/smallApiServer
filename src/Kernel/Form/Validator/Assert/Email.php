<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Form\Validator\Assert;

use \Attribute;

#[Attribute]
class Email extends AbstractAssert
{
    public function __construct(?string $errorMessage = null)
    {
        $this->allowedTypes = ['string'];
        $this->errorMessage = $errorMessage ?? 'Value %s must not be blank';
    }

    protected function check(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;;
    }
}

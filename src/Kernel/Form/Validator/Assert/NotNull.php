<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Form\Validator\Assert;

use \Attribute;
use App\Kernel\Form\Validator\ValidatorInterface;

#[Attribute]
class NotNull implements ValidatorInterface
{
private string $errorMessage;
    
    public function __construct(?string $errorMessage = null)
    {
        $this->errorMessage = $errorMessage ?? 'Value %s must not be null';
    }

    public function validate(mixed $value): bool
    {
        return null !== $value;
    }
}
<?php

namespace App\Kernel\Form\Validator\Assert;

use App\Kernel\Form\Validator\ValidatorInterface;

abstract class AbstractAssert implements ValidatorInterface
{
    protected string $errorMessage = '';

    abstract public function validate(mixed $value): bool;

    public function getMessage(): string
    {
        return $this->errorMessage;
    }
} 
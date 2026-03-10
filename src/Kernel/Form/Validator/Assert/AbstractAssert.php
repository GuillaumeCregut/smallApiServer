<?php

namespace App\Kernel\Form\Validator\Assert;

use App\Kernel\Form\Validator\ValidatorInterface;

abstract class AbstractAssert implements ValidatorInterface
{
    protected string $errorMessage = '';
    protected array $allowedTypes = [];

    public function validate(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }
        if (!$this->isValidType($value)) {
            return false;
        }
        return $this->check($value);
    }

    public function getMessage(): string
    {
        return $this->errorMessage;
    }

    abstract protected function check(mixed $value);

    protected function isValidType(mixed $value): bool
    {
        foreach ($this->allowedTypes as $type) {
            if ($type === gettype($value)) {
                return true;
            }
        }
        return false;
    }
}

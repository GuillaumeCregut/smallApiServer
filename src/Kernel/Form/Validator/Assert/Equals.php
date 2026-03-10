<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Form\Validator\Assert;

use \Attribute;

/**
 * Validates that a  value is equals to expected.
 * Note: null values pass through — use #[NotNull] if null should be rejected.
 */
#[Attribute]
class Equals extends AbstractAssert
{
    private mixed $expected = null;
    public function __construct(mixed $expected,?string $errorMessage = null)
    {
        $this->expected = $expected;
        $this->errorMessage = $errorMessage ?? "property %s must be equals to " . (is_scalar($expected) || $expected === null ? (string)$expected : gettype($expected));
        $this->allowedTypes = ['string', 'double', 'integer', 'boolean', 'NULL'];
    }

    public function validate(mixed $value): bool 
    {
        if (!$this->isValidType($value)) return false;
        return $this->check($value);
    }

    protected function check(mixed $value): bool
    {
        return $value === $this->expected;
    }
}

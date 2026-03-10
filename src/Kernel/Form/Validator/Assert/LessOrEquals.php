<?php

namespace App\Kernel\Form\Validator\Assert;

use \Attribute;


/**
 * Validates that a numeric value is less or equals than expected.
 * Note: null values pass through — use #[NotNull] if null should be rejected.
 */
#[Attribute]
class LessOrEquals extends AbstractAssert
{

    private float |int $expected;
    
    public function __construct(float |int $expected,?string $errorMessage = null)
    {
        $this->expected = $expected;
        $this->errorMessage = $errorMessage ?? "property %s must be less or equals than " . (is_scalar($expected) || $expected === null ? (string)$expected : gettype($expected));
        $this->allowedTypes = ['double', 'integer'];
    }
    protected function check(mixed $value): bool
    {
        return $value <= $this->expected;
    }
}

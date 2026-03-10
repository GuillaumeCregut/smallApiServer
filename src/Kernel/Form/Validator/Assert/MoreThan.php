<?php

namespace App\Kernel\Form\Validator\Assert;

use \Attribute;


/**
 * Validates that a numeric value is more than expected.
 * Note: null values pass through — use #[NotNull] if null should be rejected.
 */
#[Attribute]
class MoreThan extends AbstractAssert
{

    private float |int $expected;
    
    public function __construct(float |int $expected,?string $errorMessage = null)
    {
        $this->expected = $expected;
        $this->errorMessage = $errorMessage ?? "property %s must be more than " . (is_scalar($expected) || $expected === null ? (string)$expected : gettype($expected));
        $this->allowedTypes = ['double', 'integer'];
    }
    protected function check(mixed $value): bool
    {
        return $value > $this->expected;
    }
}

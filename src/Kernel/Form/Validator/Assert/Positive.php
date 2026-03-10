<?php

namespace App\Kernel\Form\Validator\Assert;

use \Attribute;


/**
 * Validates that a numeric value is more than expected.
 * Note: null values pass through — use #[NotNull] if null should be rejected.
 */
#[Attribute]
class Positive extends MoreThan
{
    
    public function __construct(?string $errorMessage = null)
    {
        parent::__construct(0,$errorMessage ?? "property %s must be positive");
    }
}
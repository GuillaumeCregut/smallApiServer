<?php

namespace App\Kernel\Form\Validator\Assert;

use \Attribute;


/**
 * Validates that a numeric value is greater than expected.
 * Note: null values pass through — use #[NotNull] if null should be rejected.
 */
#[Attribute]
class Min extends MoreOrEquals
{}
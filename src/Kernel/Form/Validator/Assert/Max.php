<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Form\Validator\Assert;

use \Attribute;


/**
 * Validates that a numeric value is less than expected.
 * Note: null values pass through — use #[NotNull] if null should be rejected.
 */
#[Attribute]
class Max extends LessOrEquals
{}
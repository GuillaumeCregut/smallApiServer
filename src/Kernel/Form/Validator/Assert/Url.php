<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Form\Validator\Assert;

use \Attribute;

#[Attribute]
class Url extends AbstractAssert
{
    public function __construct(?string $errorMessage = null)
    {
        $this->errorMessage = $errorMessage ?? 'property %s must be a valid URL';
        $this->allowedTypes = ['string'];
    }

    protected function check(mixed $value): bool
    {
        if (false === filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }
        $schema = parse_url($value, PHP_URL_SCHEME);
        return in_array($schema, ['http', 'https', 'ftp']);
    }
}

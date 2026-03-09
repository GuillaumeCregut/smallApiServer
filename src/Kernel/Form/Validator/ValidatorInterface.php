<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Form\Validator;

interface ValidatorInterface
{
    public function validate(mixed $value): bool;
}
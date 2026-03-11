<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Form\Validator\Assert;

use \Attribute;
use App\Kernel\Files\FileUpload;

#[Attribute]
class File extends AbstractAssert
{
    public function __construct(
        private int $maxSize,
        private array $allowedMimeTypes = [],
        private array $allowedExtensions = [],
        ?string $errorMessage = null
    ) {
        $this->errorMessage = $errorMessage ?? 'property %s contains an invalid file';
        $this->allowedTypes = [];
    }

    public function validate(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }
        if (!$value instanceof FileUpload) {
            return false;
        }
        return $value->isValid($this->maxSize, $this->allowedMimeTypes, $this->allowedExtensions);
    }

    protected function check(mixed $value): bool
    {
        return true;
    }
}

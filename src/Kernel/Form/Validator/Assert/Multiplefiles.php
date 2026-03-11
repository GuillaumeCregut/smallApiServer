<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Form\Validator\Assert;

use \Attribute;
use App\Kernel\Files\FileUpload;

#[Attribute]
class Multiplefiles extends AbstractAssert
{
    public function __construct(
        private int $maxSize,
        private array $allowedMimeTypes = [],
        private array $allowedExtensions = [],
        ?string $errorMessage = null
    ) {
        $this->errorMessage = $errorMessage ?? 'property %s contains an invalid file';
        $this->allowedTypes = ['array'];
    }

    public function validate(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }
        if (!is_array($value) || (empty($value))) {
            return false;
        }
        
        foreach ($value as $file) {
            if (!$file instanceof FileUpload) {
                return false;
            }
            $result = $file->isValid($this->maxSize, $this->allowedMimeTypes, $this->allowedExtensions);
            if (!$result) {
                return false;
            }
        }
        return true;
    }

    protected function check(mixed $value): bool
    {
        return true;
    }
}

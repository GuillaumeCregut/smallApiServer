<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Form;

use App\Kernel\Form\Caster\DataCaster;
use App\Kernel\Form\Sanitizer\DataSanitizer;
use App\Kernel\Form\Validator\DataValidator;

class FormHandler
{
    /**
     * will sanitize, cast and validate datas from Request (coming from $_POST or json and $_FILES) to match Entity requirements
     *
     * @param string $entity
     * @param array $rawValues
     * @param array $files
     * @return array containing result f tests, values casted and errors if error occured
     */
    public static function handle(string $entity, array $rawValues, array $files=[]): array
    {
        $sanitized = DataSanitizer::sanitize($rawValues);
        $casted    = DataCaster::cast($entity, $sanitized);
        $valid     = DataValidator::validate($entity, $casted, $files);

        return [
            'valid'  => $valid,
            'values' => $casted, // ready to hydrate
            'errors' => DataValidator::getErrors()
        ];
    }
}

<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Psr14\Exceptions;

use Exception;

class EventException extends Exception
{
     public function __construct(string $message, ?int $code =0)
    {
        parent::__construct($message, $code);
    }
}
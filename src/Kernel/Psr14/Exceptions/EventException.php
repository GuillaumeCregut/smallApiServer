<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Psr14\Exceptions;

use Exception;

class EventException extends Exception
{
     public function __construct(string $message)
    {
        parent::__construct($message, 0);
    }
}
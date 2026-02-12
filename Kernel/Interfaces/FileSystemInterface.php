<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Interfaces;

interface FileSystemInterface
{
    public function isUploadedFile(string $filename): bool;
    public function moveUploadedFile(string $from, string $to): bool;
}
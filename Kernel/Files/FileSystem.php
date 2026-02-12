<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Files;

use App\Kernel\Interfaces\FileSystemInterface;

class FileSystem implements FileSystemInterface
{
    public function isUploadedFile(string $filename): bool
    {
        return is_uploaded_file($filename);
    }

    public function moveUploadedFile(string $from, string $to): bool
    {
        return move_uploaded_file($from, $to);
    }
}
<?php

use App\Kernel\Interfaces\FileSystemInterface;

class TestFileSystem implements FileSystemInterface
{
    public function isUploadedFile(string $filename): bool
    {
        // In tests, just check if file exists
        return file_exists($filename);
    }

    public function moveUploadedFile(string $from, string $to): bool
    {
        // Use regular copy/rename for tests
        return copy($from, $to) && unlink($from);
    }
}
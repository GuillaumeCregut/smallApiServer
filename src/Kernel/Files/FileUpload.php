<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Files;

use App\Kernel\Interfaces\FileSystemInterface;
use Error;
use Exception;
use SplFileInfo;
use App\Kernel\Files\FileSystem;

class FileUpload extends SplFileInfo
{
    private string $name;
    private string $mimeType;
    private string $tmp_name;
    private int $size;
    private int $error;
    private string $full_path;
    private bool $fileOK = false;
    private FileSystemInterface $fileSystem;

    public function __construct(array $fileData, ?FileSystemInterface $fileSystem = null)
    {
        $this->name = $fileData['name'];
        $this->mimeType = $fileData['type'] ?: 'application/octet-stream';
        $this->tmp_name = $fileData['tmp_name'];
        $this->size = $fileData['size'];
        $this->error = $fileData['error'];
        $this->full_path = $fileData['full_path'] ?? '';
        $this->fileSystem = $fileSystem ?? new FileSystem();
        parent::__construct($this->tmp_name);
    }
    public function isValid(int $maxSize, array $allowedMimeTypes = [], array $allowedExtensions = []): bool
    {
        $isOK = \UPLOAD_ERR_OK === $this->error;

        if (!is_file($this->tmp_name)) {
            $isOK = false;
        }
        if ($this->size > $maxSize) {
            $isOK = false;
        }

        if ($this->hasDoubleExtension()) {
            $isOK = false;
        }

        $realMimeType = $realMimeType = $this->detectRealMimeType();
        $this->mimeType = $realMimeType;
        if (!empty($allowedMimeTypes) && !in_array($realMimeType, $allowedMimeTypes, true)) {
            $isOK = false;
        }

        if (!empty($allowedExtensions)) {
            $extension = strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                $isOK = false;
            }
        }

        if (in_array($realMimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            if (!$this->isValidImage()) {
                $isOK = false;
            }
        }

        $this->fileOK = $isOK && $this->fileSystem->isUploadedFile($this->getPathname());
        return $this->fileOK;
    }

    public function move(string $directory, ?string $name = null): FileUpload
    {
        if (!$this->fileOK) {
            throw new Exception("The file is not valid");
        }
        if (!is_dir($directory)) {
            if (false === mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new Exception(sprintf("Directory %s can't be created", $directory));
            }
            if (!is_writable($directory)) {
                throw new Exception(sprintf('%s is not writable', $directory));
            }
        }
        $targetFullPath = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR .
            (null === $name ? $this->getBasename() : $this->getFName($name));
        $target = new self(
            [
                'name' => $name ?? $this->name,
                'type' => $this->mimeType,
                'tmp_name' => $targetFullPath,
                'size' => $this->size,
                'error' => UPLOAD_ERR_OK,
                'full_path' => $targetFullPath,
            ],
            $this->fileSystem
        );
        try {
            $moved = $this->fileSystem->moveUploadedFile($this->getPathname(), $targetFullPath);
        } catch (Error $e) {
            throw new Exception($this->getErrorMessage($this->error));
        }
        if (!$moved) {
            throw new Exception("Could not move the file");
        }
        @chmod($targetFullPath, 0666 & ~umask()); // Set permissions on the moved file to 0666 max  
        return $target;
    }
    /**
     * Get the value of name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the value of mimeType
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Get the value of size
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Get the value of error
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Get the value of full_path
     */
    public function getFullPath(): string
    {
        return $this->full_path;
    }


    public static function fromPath(string $path): self
    {
        return new self([
            'name'      => basename($path),
            'type'      => '',
            'tmp_name'  => $path,
            'size'      => 0,
            'error'     => UPLOAD_ERR_OK,
            'full_path' => $path,
        ]);
    }
    
    private function getFName(string $name): string
    {
        $tmpName = str_replace('\\', DIRECTORY_SEPARATOR, $name);
        $pos = strrpos($tmpName, '/');
        if (!$pos) {
            return $name;
        }
        return substr($tmpName, $pos + 1);
    }
    private function getErrorMessage(int $error): string
    {
        static $errorsMessages = [
            \UPLOAD_ERR_INI_SIZE => 'The file exceeds your upload_max_filesize ini directive',
            \UPLOAD_ERR_FORM_SIZE => 'The file exceeds the upload limit defined in your form.',
            \UPLOAD_ERR_PARTIAL => 'The file  was only partially uploaded.',
            \UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            \UPLOAD_ERR_CANT_WRITE => 'The file  could not be written on disk.',
            \UPLOAD_ERR_NO_TMP_DIR => 'File could not be uploaded: missing temporary directory.',
            \UPLOAD_ERR_EXTENSION => 'File upload was stopped by a PHP extension.',
        ];
        $message = $errorsMessages[$error] ?? 'The file was not uploaded due to an unknown error.';
        return $message;
    }

    private function detectRealMimeType(): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $this->tmp_name);
            if ($mimeType !== false) {
                return $mimeType;
            }
        }

        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($this->tmp_name);
            if ($mimeType !== false) {
                return $mimeType;
            }
        }

        return $this->mimeType;
    }

    private function hasDoubleExtension(): bool
    {
        $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'exe', 'sh', 'bat'];
        $filename = strtolower($this->name);

        foreach ($dangerousExtensions as $ext) {
            if (str_contains($filename, '.' . $ext . '.')) {
                return true;
            }
        }

        return false;
    }

    private function isValidImage(): bool
    {
        $imageInfo = @getimagesize($this->tmp_name);
        return $imageInfo !== false;
    }
}

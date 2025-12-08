<?php

namespace App\Kernel\Files;

use Error;
use Exception;
use SplFileInfo;

class FileUpload extends SplFileInfo
{
    private string $name;
    private string $mimeType;
    private string $tmp_name;
    private int $size;
    private int $error;
    private string $full_path;
    private bool $fileOK = false;

    public function __construct(array $fileData)
    {
        $this->name = $fileData['name'];
        $this->mimeType = $fileData['type'] ?: 'application/octet-stream';
        $this->tmp_name = $fileData['tmp_name'];
        $this->size = $fileData['size'];
        $this->error = $fileData['error'];
        $this->full_path = $fileData['full_path'] ?? '';
        parent::__construct($this->tmp_name);
    }
    public function isValid(int $maxSize, array $allowedMimeTypes = []): bool
    {
        $isOK = \UPLOAD_ERR_OK === $this->error;

        if (!is_file($this->tmp_name)) {
           $isOK = false;
        }
        if ($this->size > $maxSize) {
            $isOK = false;
        }
        if (!in_array($this->mimeType, $allowedMimeTypes)) {
            $isOK = false;
        }

        $this->fileOK = $isOK && is_uploaded_file($this->getPathname());
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
            ]);
        try {
                $moved = move_uploaded_file($this->getPathname(), $target);
            } catch (Error $e) {
                throw new Exception($this->getErrorMessage($this->error));
            }
            if (!$moved) {
                throw new Exception("Could not move the file");
            }
            @chmod($target, 0666 & ~umask()); // Set permissions on the moved file to 0666 max  
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
}
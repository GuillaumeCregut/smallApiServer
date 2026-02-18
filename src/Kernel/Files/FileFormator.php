<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Files;

class FileFormator
{
    public static function convert(array $files): array
    {
        $filesArray = [];
        foreach($files as $key => $fileElement) {
            if(is_array($fileElement['name'])) {
                $filesArray[$key] = self::formatMultipleFiles($fileElement);
            } else {
                $filesArray[$key] = [array(
                    'name'  => $fileElement['name'],
                    'type'  => $fileElement['type'],
                    'tmp_name' => $fileElement['tmp_name'],
                    'size'  => $fileElement['size'],
                    'error' => $fileElement['error'],
                    'full_path' => $fileElement['full_path'])
                ];
            }
        }
        return $filesArray;
    }

    private static function formatMultipleFiles(array $fileElement): array
    {
        $files = [];
        $fileCount = count($fileElement['name']);
        for($i = 0; $i < $fileCount; $i++) {
            $files[] = [
                'name' => $fileElement['name'][$i],
                'type' => $fileElement['type'][$i],
                'tmp_name' => $fileElement['tmp_name'][$i],
                'error' => $fileElement['error'][$i],
                'size' => $fileElement['size'][$i],
                'full_path' => $fileElement['full_path'][$i],
            ];
        }
        return $files;
    }
    
}
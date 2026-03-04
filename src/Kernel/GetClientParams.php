<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel;

use App\Kernel\Exceptions\KernelException;
use Exception;

class GetClientParams
{
    public static function getInputs(): array
    {
        try {
            $inputs = file_get_contents("php://input");
            if (! is_string($inputs)) {
                throw new KernelException('User inputs format not supported');
            }
            $json = self::decodeJSON($inputs);
            if (!is_array($json)) {
                $json = [];
            }
            return $json;
        } catch (Exception $e) {
            Logger::error(self::class, "{$e->getMessage()} : {$inputs}", false, false);
            throw new KernelException($e->getMessage());
            return [];
        }
    }

    public static function getheaders(): array
    {
        return getallheaders();
    }

    private static function decodeJSON(string $json): array
    {
        if (! is_string($json)) {
            throw new KernelException('User inputs (not string) format not supported');
        }
        if('""' === $json){
            return [];
        }
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error(self::class, "Json error decoding : {$json}", false, false);
            throw new KernelException('Error decoding JSON');
        } else {
        }

        if (!is_array($data)) {
            Logger::error(self::class, "Json error decoding : {$json}", false, false);
            throw new KernelException('Json not decoded');
        }
        return $data;
    }
}

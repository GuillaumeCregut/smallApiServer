<?php

namespace App\Kernel;

class GetClientParams
{
    public static function getInputs(): array
    {
        $json = self::decodeJSON(file_get_contents("php://input"));
        return $json;
    }

    private static function decodeJSON(string $json): array
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        return $data;
    }

    public static function getheaders(): array
    {
        return getallheaders();
    }
}
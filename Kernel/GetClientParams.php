<?php

namespace App\Kernel;

use Exception;

class GetClientParams
{
    public static function getInputs(): array
    {
        try{
            $json = self::decodeJSON(file_get_contents("php://input"));
            if(!is_array($json)){
                $json = [];
            }
            return $json;
        } catch (Exception $e) {
            return [];
        }
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
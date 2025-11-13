<?php

namespace App\Services;

class RequestObject
{
    private string $method;
    private array $datas;
    private array $headers;
    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->datas = $this->getDatas();
        $this->headers = getallheaders();
    }

    private function decodeJSON(string $json): array
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        return $data;
    }

    private function getDatas(): array
    {
        $method = $this->method;
        $datas = [];

        switch ($method) {
            case 'GET':
                 $json =  file_get_contents("php://input");
                $datas = array_merge($this->decodeJSON($json), $_GET);
                break;
            case 'POST':
                $json =  file_get_contents("php://input");
                $datas = array_merge($_POST,$this->decodeJSON($json), $_GET);
                break;
            case 'PUT':
                $json =  file_get_contents("php://input");
                $datas = array_merge($datas, $this->decodeJSON($json), $_GET);
                break;
            case 'PATCH':
                $json =  file_get_contents("php://input");
                $datas = array_merge($datas, $this->decodeJSON($json), $_GET);
                break;
            case 'DELETE':
                $json =  file_get_contents("php://input");
                $datas = array_merge($datas, $this->decodeJSON($json), $_GET);
                break;
            default:
                break;
        }
        return $datas;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getAllDatas(): array
    {
        return $this->datas;
    }

    public function isAuth(): bool
    {
        //Todo : make authentication
        return false;
    }

    /* Returns the Authorization header content as an array [type, credentials] or null if not present */
    public function getAuthUser(): ?array
    {
         
        $autorisation = $this->headers['Authorization'] ?? null;
        if ($autorisation === null) {
            return null;
        } 
        $auth= explode(' ', $autorisation,2);
        return $auth;
    }
}
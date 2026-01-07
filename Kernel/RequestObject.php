<?php

namespace App\Kernel;

use App\Kernel\Interfaces\AuthenticationInterface;
use App\Kernel\Files\FileFormator;
use App\Kernel\Files\FileUpload;
use App\Security\User;

class RequestObject
{
    private static ?RequestObject $instance = null;
    private string $method;
    private array $datas;
    private array $headers;
    private array $files;
    private array $server;
    private array $sessions;
    private ?AuthenticationInterface $auth = null;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->datas = $this->getDatas();
        $this->headers = getallheaders();
        $this->server = $_SERVER;
        $this->sessions = $_SESSION;
        $this->files = $this->convertFiles();
    }

    private function decodeJSON(string $json): array
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        return $data;
    }
    
    private function convertFiles(): array
    {
        $files = FileFormator::convert($_FILES);
        $fileContainer = [];
        foreach ($files as $key=>$fileFields) {
          
            foreach ($fileFields as $file) {
            $file = new FileUpload($file); //, ['image/jpeg', 'image/png', 'application/pdf']
            $fileContainer[$key][] = $file;
            }
        }
        return $fileContainer;
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

     private function makeRoute(string $route): string
    {
        $route = filter_var($route, FILTER_SANITIZE_URL);
        $routes = explode('/', $route);
        $id = end($routes);
        if (is_numeric($id)) {
            if(!key_exists('id', $this->datas)) {
                $this->setData('id', (int)$id);
            } 
            array_pop($routes);
        }
        return implode('/', $routes);
        // Remove any unwanted characters from the route
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
        if($this->auth === null){
            return false;
        }
        return $this->auth->isAuth();
    }

    public function setData(string $key, mixed $value): void
    {
        $this->datas[$key] = $value;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function getFile(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public static function getRequestInstance(): RequestObject
    {
        if (is_null(self::$instance)) {
            self::$instance = new RequestObject();
        }
        return self::$instance;
    } 

    public function getURI(): string
    {
        $route = $this->makeRoute( trim(parse_url($this->server['REQUEST_URI'], PHP_URL_PATH) ?? '', '/'));
        return $route;
    }

    public function getSessionValue(string $name): mixed
    {
        return $this->sessions[$name] ?? null;
    }

    public function setSessionValue(string $name, mixed $value): void
    {
        $this->sessions[$name] = $value;
        $_SESSION[$name] = $value;
    }

    /**
     * Get the value of server
     */
    public function getServer(string $name): string|null
    {
        return $this->server[$name] ?? null;
    }

    /**
     * Get the value of headers
     */
    public function getHeaders(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Set the value of auth
     */
    public function setAuth(?AuthenticationInterface $auth): void
    {
        $this->auth = $auth;
    }

    public function getUser():?User
    {
        if($this->auth === null){
            return null;
        }
        return $this->auth->getUser();
    }
}
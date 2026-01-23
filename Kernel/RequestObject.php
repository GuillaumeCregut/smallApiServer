<?php

namespace App\Kernel;

use App\Kernel\Interfaces\AuthenticationInterface;
use App\Kernel\Files\FileFormator;
use App\Kernel\Files\FileUpload;
use App\Security\User;

class RequestObject
{
    private static ?RequestObject $instance = null;
    private string $method = '';
    private array $datas = [];
    private array $headers = [];
    private array $files = [];
    private array $server = [];
    private array $sessions = [];
    private bool $isInit = false;
    private bool $refererValid = false;
    private ?AuthenticationInterface $auth = null;

    public function __construct()
    {
        /* $this->method =
        $datas = $this->getDatas($_GET, $_POST, []); //Remove in next version
       $this->method = $_SERVER['REQUEST_METHOD']; //Remove in next version
        $this->headers = getallheaders(); //Remove in next version
        $this->server = $_SERVER; //Remove in next version
        $this->sessions = $_SESSION; //Remove in next version
        $this->files = $this->convertFiles($_FILES); //Remove in next version
        if (!$this->isInit) {
            self::initInstance($_SERVER, $datas, $_GET, $_POST, $_FILES, $_SESSION, getallheaders());
            $this->isInit = true;
        }*/
    }

    public static function initInstance(array $server, array $datas, array $get, array $post, array $files, array $session, array $headers): RequestObject
    {
        if (is_null(self::$instance)) {
            self::$instance = new RequestObject();
        }
        //Ici j'ai des doutes..... les accès sont privés
        $request = self::$instance;
        $request->method = $server['REQUEST_METHOD'] ?? '';
        $request->datas = $request->getDatas($get, $post, $datas);
        $request->headers = $headers;
        $request->server = $server;
        $request->sessions = $session;
        $request->files = $request->convertFiles($files);
        $request->isInit = true;
        if (!isset($server['HTTP_REFERER'])) {
            $request->refererValid = false;
        } else {
            $referer = parse_url($server['HTTP_REFERER'], PHP_URL_HOST);
            $host = $server['HTTP_HOST'] ?? '';
            $request->refererValid = (($referer['host'] ?? '') === $host);
        }

        self::$instance = $request;
        return self::$instance;
    }
    private function decodeJSON(string $json): array
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        return $data;
    }

    private function convertFiles(?array $filesFromInit = null): array
    {
        if (null === $filesFromInit) {
            $filesFromInit = $_FILES;
        }
        $files = FileFormator::convert($filesFromInit);
        $fileContainer = [];
        foreach ($files as $key => $fileFields) {

            foreach ($fileFields as $file) {
                $file = new FileUpload($file); //, ['image/jpeg', 'image/png', 'application/pdf']
                $fileContainer[$key][] = $file;
            }
        }
        return $fileContainer;
    }

    private function getDatas(array $get, array $post, array $datas): array
    {
        $method = $this->method;
        $datas = [];
        if (empty($get)) {
            $get = $_GET;
        }
        if (empty($post)) {
            $post = $_POST;
        }
        if (empty($datas)) {
            $datas = [];
        }
        $json = file_get_contents("php://input");

        switch ($method) {
            case 'GET':
                $datas = array_merge($this->decodeJSON($json), $_GET);
                break;
            case 'POST':
                $datas = array_merge($_POST, $this->decodeJSON($json), $_GET);
                break;
            case 'PUT':
                $datas = array_merge($datas, $this->decodeJSON($json), $_GET);
                break;
            case 'PATCH':
                $datas = array_merge($datas, $this->decodeJSON($json), $_GET);
                break;
            case 'DELETE':
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
            if (!key_exists('id', $this->datas)) {
                $this->setData('id', (int)$id);
            }
            array_pop($routes);
        }
        return implode('/', $routes);
        // Remove any unwanted characters from the route
    }

    private function getRefererValid(): bool
    {
        if (!isset($this->server['HTTP_REFERER'])) {
            return false;
        }
        $referer = parse_url($this->server['HTTP_REFERER'], PHP_URL_HOST);
        $host = $this->server['HTTP_HOST'] ?? '';
        return (($referer['host'] ?? '') === $host);
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
        if ($this->auth === null) {
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
            self::initInstance($_SERVER, [], $_GET, $_POST, $_FILES, $_SESSION, getallheaders());
        }
        return self::$instance;
    }

    public function getURI(): string
    {
        $route = $this->makeRoute(trim(parse_url($this->server['REQUEST_URI'], PHP_URL_PATH) ?? '', '/'));
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
     * Get the value of isRefererValid
     */
    public function isRefererValid(): bool
    {
        return $this->refererValid;
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

    public function getUser(): ?User
    {
        if ($this->auth === null) {
            return null;
        }
        return $this->auth->getUser();
    }
}

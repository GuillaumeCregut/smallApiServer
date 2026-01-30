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

    public static function initInstance(array $server, array $datas, array $get, array $post, array $files, array $session, array $headers): RequestObject
    {
        if (is_null(self::$instance)) {
            self::$instance = new RequestObject();
        }
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
            $referer = parse_url($server['HTTP_REFERER']) ?? '';
            $host = parse_url($server['HTTP_HOST']) ?? '';
            $ok = (($referer['host'] === $host['host']) && ($referer['port'] === $host['port']));
            $request->refererValid = $ok;
        }

        self::$instance = $request;
        return self::$instance;
    }
    public static function getRequestInstance(): RequestObject
    {
        if (is_null(self::$instance)) {
            self::$instance = new RequestObject();
            self::initInstance($_SERVER, [], $_GET, $_POST, $_FILES, $_SESSION, getallheaders());
        }
        return self::$instance;
    }
    public function getMethod(): string
    {
        return $this->method;
    }

    public function getAllDatas(): array
    {
        return $this->datas;
    }

    public function getData(string $name): mixed
    {
        return $this->datas[$name] ?? null;
    }

    public function setData(string $key, mixed $value): void
    {
        $this->datas[$key] = $value;
    }

    public function isAuth(): bool
    {
        //Todo : make authentication
        if ($this->auth === null) {
            return false;
        }
        return $this->auth->isAuth();
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function getFile(string $key): ?array
    {
        return $this->files[$key] ?? null;
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

    private function convertFiles(?array $filesFromInit = null): array
    {
        if (null === $filesFromInit) {
            $filesFromInit = $_FILES;
        }
        $files = FileFormator::convert($filesFromInit);
        $fileContainer = [];
        foreach ($files as $key => $fileFields) {

            foreach ($fileFields as $file) {
                $file = new FileUpload($file);
                $fileContainer[$key][] = $file;
            }
        }
        return $fileContainer;
    }

    private function getDatas(array $get, array $post, array $datas): array
    {
        $allDatas = array_merge($get, $post, $datas);
        return $allDatas;
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
        $newRoute = implode('/', $routes);
        return $newRoute;
        // Remove any unwanted characters from the route
    }
}

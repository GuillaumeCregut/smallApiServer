<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel;

use App\Kernel\Interfaces\AuthenticationInterface;
use App\Kernel\Files\FileFormator;
use App\Kernel\Files\FileUpload;
use App\Security\User;

class Request
{
    private static ?Request $instance = null;
    private string $method = '';
    private array $datas = [];
    private array $headers = [];
    private array $files = [];
    private array $server = [];
    private array $sessions = [];
    private bool $isInit = false;
    private bool $refererValid = false;
    private array $cookies = [];
    private array $userParam = [];
    private bool $connected = false;
    private ?User $user = null;


    public static function initInstance(array $server, array $datas, array $get, array $post, array $files, array $session, array $headers, ?array $cookies = []): Request
    {
        if (is_null(self::$instance)) {
            self::$instance = new Request($server, $datas, $get, $post, $files, $session, $headers, $cookies);
        }
        return self::$instance;
    }

    public static function getRequestInstance(): Request
    {
        if (is_null(self::$instance)) {
            self::initInstance($_SERVER, [], $_GET, $_POST, $_FILES, $_SESSION, getallheaders(), $_COOKIE);
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

    public function getFiles(): array
    {
        return $this->files;
    }

    public function getFile(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Get from URI the corresponding route key. Return null if no key found
     * Internally, set Id for composite route, in $this->datas
     *
     * @param array|null $routes
     * @return string|null key route if found, null else
     */
    public function getURI(array $routes = []): ?string
    {
        $uri = trim(parse_url($this->server['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');
        $route = $this->makeRoute($uri, $routes);
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

    public function isRefererValid(): bool
    {
        return $this->refererValid;
    }
   
    public function getServer(string $name): string|null
    {
        return $this->server[$name] ?? null;
    }

    public function getHeaders(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function getCookie(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }

    public function addParam(string $name, mixed $value): self
    {
        $this->userParam[$name] = $value;
        return $this;
    }

    public function getParam(string $name): mixed
    {
        return $this->userParam[$name] ?? null;
    }

    public function getParams(): array
    {
        return $this->userParam;
    }
    
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public function isIsInit(): bool
    {
        return $this->isInit;
    }

    public function isConnected(): bool
    {
        return null !== $this->user;
    }

    private function __construct(
        array $server,
        array $datas,
        array $get,
        array $post,
        array $files,
        array $session,
        array $headers,
        array $cookies
    ) {
        $this->method = $server['REQUEST_METHOD'] ?? '';
        $this->server = $server;
        $this->datas = $this->getDatas($get, $post, $datas);
        $this->headers = $headers;
        $this->sessions = $session;
        $this->cookies = $cookies;
        $this->files = $this->convertFiles($files);
        $this->isInit = true;
        if (!isset($server['HTTP_REFERER'])) {
            $this->refererValid = false;
        } else {
            $referer = parse_url($server['HTTP_REFERER']) ?? '';
            $host = parse_url($server['HTTP_HOST']) ?? '';
            if('' === $referer || '' === $host) {
                return false;
            }
            if (!isset($referer['host']) || !isset($host['host']) || !isset($referer['port']) || !isset($host['port'])){
                return false;
            }
            $ok = (($referer['host'] === $host['host']) && ($referer['port'] === $host['port']));
            $this->refererValid = $ok;
        }
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

    private function makeRoute(string $route, array $routes=[]): ?string
    {
        $route = filter_var($route, FILTER_SANITIZE_URL);
        if('' === $route) {
            return $route;
        }
        $result = RouteCompiler::findRoute($route, $routes);
        
        if(null === $result) {
            return null;
        }
        if(!array_key_exists('routeName', $result)) {
            return null;
        }
        
        $newRoute = $result['routeName'];
        foreach($result as $key => $value) {
            if('routeName' === $key) {
                continue;
            }
            $this->datas[$key] = is_numeric($value) ? (int)$value : $value;
        }
        return $newRoute;
    }
}

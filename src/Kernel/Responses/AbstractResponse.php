<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Responses;

use App\Kernel\GetEnvDatas;
use App\Kernel\utils\Dumper;
use App\Kernel\Exceptions\KernelException;
use App\Kernel\Interfaces\ResponseInterface;

abstract class AbstractResponse implements ResponseInterface
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected string $body = '';
    protected array $cookies = [];
    abstract public function setBody(mixed $content): self;
    abstract public function sendReponse(): void;
    abstract protected function displayDump(): void;

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        /*
        HTTP headers are built like this :
        Header-Name: Header Value
        */
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(): string
    {
        // Send status code
        $this->sendReponse();
        http_response_code($this->statusCode);
        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        header("x-powered-by:");
        $this->sendCookies();
        $this->DisplayDebugMode();
        // Send body
        return $this->body;
    }

    //for tests purposes
    public function getBody():string
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setCookie(string $name, string $value, ?array $params=[]): self
    {
        /*
        $params = [
        'domain'=> string,
        'path' => string,
        'sameSite'=>string,
        'expire' => int
        'security'=>int]
        */
        $params['value'] = $value;
        $this->cookies[$name] = $params;
        return $this;
    }

    private function DisplayDebugMode(): void
    {
        try {
            $isDebug = GetEnvDatas::getEnvInstance()->get('DEBUG_MODE', false);
            if ($isDebug && class_exists(Dumper::class)) {
                $this->displayDump();
            }
        } catch (KernelException $e) {
        }
    }

    private function sendCookies(): void
    {
        $sameSiteValues =['Lax', 'None', 'Strict'];
        /*each cookie =
         [
        'value'=>string
        'domain'=> string,
        'path' => string,
        'sameSite'=>string,
        'expire' => int
        'security'=>int]
        */
        foreach($this->cookies as $name => $cookie) {
            $value = $cookie['value'];
            if(key_exists('domain', $cookie)){
                $domain = $cookie['domain'];
            } else {
                $domain =''; 
            }
            if(key_exists('path', $cookie)){
                $path= $cookie['path'];
            } else {
                $path=''; 
            }
            if(key_exists('security', $cookie)){
                $security = $cookie['security'];
                if (0 === $security) {
                     $secure=false;
                     $httpOnly = false;
                } else {
                    $secure = $security & Cookie::COOKIE_SECURE;
                    $httpOnly = $security & Cookie::COOKIE_HTTPONLY;
                }
            } else {
                $secure=false; 
                $httpOnly = false;
            }
             if(key_exists('sameSite', $cookie)){
                $sameSite= $cookie['sameSite'];
                if(!in_array($sameSite, $sameSiteValues)) {
                    $sameSite = null;
                }
                if("None" === $sameSite) {
                    $secure = true;
                }
            } else {
                $sameSite= null; 
            }
            if(key_exists('expire', $cookie)){
                $expire = $cookie['expire'];
            } else {
                $expire =0;
            }
            $options =[
                'expires'=>$expire,
                'path'=>$path,
                'domain'=>$domain,
                'secure'=>$secure,
                'httponly'=>$httpOnly
            ];
            if (null !== $sameSite) {
                $options['samesite'] = $sameSite;
            }
            setcookie($name, $value,$options);
        }
    }
}

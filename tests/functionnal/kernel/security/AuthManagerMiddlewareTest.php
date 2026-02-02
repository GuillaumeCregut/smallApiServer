<?php

use App\Kernel\Connector\MySQLConnector;
use App\Kernel\Request;
use App\Services\Connector;
use PHPUnit\Framework\TestCase;
use App\Kernel\Middleware\Security\HttpAuthMiddleware;
use App\Kernel\Middleware\Security\AuthBearerMiddleware;
use App\Kernel\Middleware\Security\AuthManagerMiddleware;
use App\Kernel\Middleware\Security\SessionAuthMiddleware;

class AuthManagerMiddlewareTest extends TestCase
{
    public function testBearerAuth(): void
    {
        $connector = new MySQLConnector();
        $headers = [
            'Authorization' => 'Bearer YOUR_TOKEN_HERE'
        ];
        $request = Request::initInstance([], [], [], [], [], [], $headers);
        $middleware = AuthManagerMiddleware::getAuthMiddleware($connector);
        $this->assertInstanceOf(AuthBearerMiddleware::class, $middleware);
    }

    public function testHTTPAuth(): void
    {
        $connector = new MySQLConnector();
        $server = [
            'PHP_AUTH_USER' => 'Bearer YOUR_TOKEN_HERE',
            'PHP_AUTH_PW' => 'pass'
        ];
        $request = Request::initInstance($server, [], [], [], [], [], []);
        $middleware = AuthManagerMiddleware::getAuthMiddleware($connector);
        $this->assertInstanceOf(HttpAuthMiddleware::class, $middleware);
    }

    public function testSessionAuth(): void
    {
        $connector = new MySQLConnector();
        $session = [
            'userId' => 1,
        ];
        $request = Request::initInstance([], [], [], [], [], $session, []);
        $middleware = AuthManagerMiddleware::getAuthMiddleware($connector);
        $this->assertInstanceOf(SessionAuthMiddleware::class, $middleware);
    }

    public function testNoAuth(): void
    {
        $connector = new MySQLConnector();
        $request = Request::initInstance([], [], [], [], [], [], []);
        $middleware = AuthManagerMiddleware::getAuthMiddleware($connector);
        $this->assertNull($middleware);
    }

    public function testThatBearerIsUse(): void
    {
        $server = [
            'PHP_AUTH_USER' => 'Bearer YOUR_TOKEN_HERE',
            'PHP_AUTH_PW' => 'pass'
        ];
        $session = [
            'userId' => 1,
        ];
        $headers = [
            'Authorization' => 'Bearer YOUR_TOKEN_HERE'
        ];
        $connector = new MySQLConnector();
        $request = Request::initInstance($server, [], [], [], [], $session, $headers);
        $middleware = AuthManagerMiddleware::getAuthMiddleware($connector);
        $this->assertInstanceOf(AuthBearerMiddleware::class, $middleware);
    }
}

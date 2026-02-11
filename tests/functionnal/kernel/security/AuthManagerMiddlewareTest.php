<?php

use App\Kernel\Request;
use App\Kernel\GetEnvDatas;
use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\MySQLConnector;
use App\Kernel\Middleware\Security\AuthHttpMiddleware;
use App\Kernel\Interfaces\Databases\ConnectorInterface;
use App\Kernel\Middleware\Security\AuthBearerMiddleware;
use App\Kernel\Middleware\Security\AuthManagerMiddleware;
use App\Kernel\Middleware\Security\SessionAuthMiddleware;

class AuthManagerMiddlewareTest extends TestCase
{
    public function testBearerAuth(): void
    {
        GetEnvDatas::resetInstance();
        $filename = __DIR__ . DIRECTORY_SEPARATOR . '.env.sample';
        $env = GetEnvDatas::getEnvInstance($filename);
        $connector = $this->createStub(ConnectorInterface::class);
        $headers = [
            'Authorization' => 'Bearer YOUR_TOKEN_HERE'
        ];
        $request = Request::initInstance([], [], [], [], [], [], $headers);
        $middleware = AuthManagerMiddleware::getAuthMiddleware($connector);
        $this->assertInstanceOf(AuthBearerMiddleware::class, $middleware);
    }

    public function testHTTPAuth(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $server = [
            'PHP_AUTH_USER' => 'Bearer YOUR_TOKEN_HERE',
            'PHP_AUTH_PW' => 'pass'
        ];
        $request = Request::initInstance($server, [], [], [], [], [], []);
        $middleware = AuthManagerMiddleware::getAuthMiddleware($connector);
        $this->assertInstanceOf(AuthHttpMiddleware::class, $middleware);
    }

    public function testSessionAuth(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $session = [
            'userId' => 1,
        ];
        $request = Request::initInstance([], [], [], [], [], $session, []);
        $middleware = AuthManagerMiddleware::getAuthMiddleware($connector);
        $this->assertInstanceOf(SessionAuthMiddleware::class, $middleware);
    }

    public function testNoAuth(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
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
        $connector = $this->createStub(ConnectorInterface::class);
        $request = Request::initInstance($server, [], [], [], [], $session, $headers);
        $middleware = AuthManagerMiddleware::getAuthMiddleware($connector);
        $this->assertInstanceOf(AuthBearerMiddleware::class, $middleware);
    }
}

<?php

use App\Kernel\Request;
use App\Kernel\GetEnvDatas;
use PHPUnit\Framework\TestCase;
use App\Kernel\Psr14\Listener\ListenerProvider;
use App\Kernel\Psr14\Dispatcher\EventDispatcher;
use App\Kernel\Psr14\Events\CallAuthKernelEvent;
use App\Kernel\Middleware\Security\AuthHttpMiddleware;
use App\Kernel\Middleware\Security\AuthBearerMiddleware;
use App\Kernel\Middleware\Security\AuthManagerMiddleware;
use App\Kernel\Middleware\Security\SessionAuthMiddleware;

class AuthInRequestTest extends TestCase
{

    protected function setUp(): void
    {
        
    }
    public function testNoAuth(): void
    {
        GetEnvDatas::resetInstance();
        $filename = GetEnvDatas::getAppPath() . DIRECTORY_SEPARATOR .'.env';
        $env = GetEnvDatas::getEnvInstance($filename);
        EventDispatcher::resetInstance();
        $request = Request::initInstance([], [], [], [], [], [], []);
        $provider = ListenerProvider::getInstance();
        $provider->resetListeners();
        $listener = new AuthManagerMiddleware();
        $provider->addListener(CallAuthKernelEvent::class, $listener, 3);
        $event = new CallAuthKernelEvent();
        $eventDispatcher = EventDispatcher::getInstance($provider);
        $eventDispatcher->dispatch($event);
        $request2 = Request::getRequestInstance();
        $this->assertNull($request2->getAuth());
    }

    public function testHTTPAuth(): void
    {
        GetEnvDatas::resetInstance();
       $filename = GetEnvDatas::getAppPath() . DIRECTORY_SEPARATOR .'.env';
        $env = GetEnvDatas::getEnvInstance($filename);
        EventDispatcher::resetInstance();
        $server = [
            'PHP_AUTH_USER' => 'Bearer YOUR_TOKEN_HERE',
            'PHP_AUTH_PW' => 'pass'
        ];
        $request = Request::initInstance($server, [], [], [], [], [], []);
        $provider = ListenerProvider::getInstance();
        $provider->resetListeners();
        $listener = new AuthManagerMiddleware();
        $provider->addListener(CallAuthKernelEvent::class, $listener, 3);
        $event = new CallAuthKernelEvent();
        $eventDispatcher = EventDispatcher::getInstance($provider);
        $eventDispatcher->dispatch($event);
        $request2 = Request::getRequestInstance();
        $this->assertNotNull($request2->getAuth());
        $this->assertInstanceOf(AuthHttpMiddleware::class, $request2->getAuth());
    }
    public function testSessionAuth(): void
    {
        GetEnvDatas::resetInstance();
        $filename = GetEnvDatas::getAppPath() . DIRECTORY_SEPARATOR .'.env';
        $env = GetEnvDatas::getEnvInstance($filename);
        EventDispatcher::resetInstance();
        $session = [
            'userId' => 1,
        ];
        $request = Request::initInstance([], [], [], [], [], $session, []);
        $provider = ListenerProvider::getInstance();
        $provider->resetListeners();
        $listener = new AuthManagerMiddleware();
        $provider->addListener(CallAuthKernelEvent::class, $listener, 3);
        $event = new CallAuthKernelEvent();
        $eventDispatcher = EventDispatcher::getInstance($provider);
        $eventDispatcher->dispatch($event);
        $request2 = Request::getRequestInstance();
        $this->assertNotNull($request2->getAuth());
        $this->assertInstanceOf(SessionAuthMiddleware::class, $request2->getAuth());
    }
    public function testBearerAuth(): void
    {
        GetEnvDatas::resetInstance();
        $filename = GetEnvDatas::getAppPath() . DIRECTORY_SEPARATOR .'.env';
        $env = GetEnvDatas::getEnvInstance($filename);
        EventDispatcher::resetInstance();
        $headers = [
            'Authorization' => 'Bearer YOUR_TOKEN_HERE'
        ];
        $request = Request::initInstance([], [], [], [], [], [], $headers);
        $provider = ListenerProvider::getInstance();
        $provider->resetListeners();
        $listener = new AuthManagerMiddleware();
        $provider->addListener(CallAuthKernelEvent::class, $listener, 3);
        $event = new CallAuthKernelEvent();
        $eventDispatcher = EventDispatcher::getInstance($provider);
        $eventDispatcher->dispatch($event);
        $request2 = Request::getRequestInstance();
        $this->assertNotNull($request2->getAuth());
        $this->assertInstanceOf(AuthBearerMiddleware::class, $request2->getAuth());
    }
}

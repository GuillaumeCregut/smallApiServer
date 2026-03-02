<?php

use App\Security\User;
use App\Kernel\Request;
use PHPUnit\Framework\TestCase;
use App\Security\UserRepository;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Connector\DatabaseException;
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Middleware\Security\SessionAuthMiddleware;

class SessionMiddlewareAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Request::resetInstance();
        ConnectorDispatcher::resetConnector();
    }

    public function testNoUserInSession(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $session = [
            'Bonjour' => 'Monde',
        ];
        $request = Request::initInstance([], [], [], [], [], $session, []);
        $repo = new UserRepository();
        $sessionAuth = new SessionAuthMiddleware($repo);
        $this->assertNull($sessionAuth->getUser());
        $this->assertFalse($sessionAuth->isAuth());
    }

    public function testWithUnknownId(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn([]);
        ConnectorDispatcher::setConnector($connector);
        $session = [
            'UserId' => 2,
        ];
        $request = Request::initInstance([], [], [], [], [], $session, []);
        $repo = new UserRepository();
        $sessionAuth = new SessionAuthMiddleware($repo);
        $this->assertNull($sessionAuth->getUser());
        $this->assertFalse($sessionAuth->isAuth());
    }

    public function testWithDbErrorId(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn([
                'id' => 1,
                'name' => 'Doe',
                'first_name' => 'John',
                'age' => 30
            ]);
        ConnectorDispatcher::setConnector($connector);
        $session = [
            'UserId' => 1,
        ];
        $request = Request::initInstance([], [], [], [], [], $session, []);
        $repo = new UserRepository();
        $this->expectException(DatabaseException::class);
        $sessionAuth = new SessionAuthMiddleware($repo);
    }

    public function testWithKnownId(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $roles = ['USER'];
        $role = json_encode($roles);
        $connector->method('fetchQuery')
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'Doe',
                    'firstname' => 'John',
                    'roles' => $role
                ]
            ]);
        ConnectorDispatcher::setConnector($connector);
        $session = [
            'UserId' => 1,
        ];
        $request = Request::initInstance([], [], [], [], [], $session, []);
        $repo = new UserRepository();
        $sessionAuth = new SessionAuthMiddleware($repo);
        $user =  $sessionAuth->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(1, $user->getId());
        $this->assertEquals('John', $user->getFirstname());
        $this->assertTrue($sessionAuth->isAuth());
        $this->assertEquals($roles, $user->getRoles());
    }
}

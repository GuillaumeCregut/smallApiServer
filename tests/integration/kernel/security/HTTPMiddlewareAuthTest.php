<?php

use App\Security\User;
use App\Kernel\Request;
use PHPUnit\Framework\TestCase;
use App\Security\UserRepository;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Connector\DatabaseException;
use App\Kernel\Interfaces\Databases\ConnectorInterface;
use App\Kernel\Middleware\Security\AuthHttpMiddleware;

class HTTPMiddlewareAuthTest extends TestCase
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
        $server = [
            'PHP_AUTH_USER' => 'Bearer YOUR_TOKEN_HERE',
            'PHP_AUTH_PW' => 'pass'
        ];
        $request = Request::initInstance($server, [], [], [], [], [], []);
        $repo = new UserRepository();
        $httpAuth = new AuthHttpMiddleware($repo);
        $this->assertNull($httpAuth->getUser());
        $this->assertFalse($httpAuth->isAuth());
    }

    public function testWithUnknownId(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn([]);
        ConnectorDispatcher::setConnector($connector);
        $server = [
            'PHP_AUTH_USER' => 'Bearer YOUR_TOKEN_HERE',
            'PHP_AUTH_PW' => 'pass'
        ];
        $request = Request::initInstance($server, [], [], [], [], [], []);
        $repo = new UserRepository();
        $httpAuth = new AuthHttpMiddleware($repo);
        $this->assertNull($httpAuth->getUser());
        $this->assertFalse($httpAuth->isAuth());
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
        $server = [
            'PHP_AUTH_USER' => 'johndoe',
            'PHP_AUTH_PW' => 'pass'
        ];
        $request = Request::initInstance($server, [], [], [], [], [], []);
        $repo = new UserRepository();
        $this->expectException(DatabaseException::class);
        $httpAuth = new AuthHttpMiddleware($repo);
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
                    'password' =>'$2y$12$5a6OfknvekRlqMQSkQ8pFO04Lldfr8htT34os6bnafDUvwOCeJ4u2',
                    'roles' => $role
                ]
            ]);
        ConnectorDispatcher::setConnector($connector);
        $server = [
            'PHP_AUTH_USER' => 'johndoe',
            'PHP_AUTH_PW' => 'pass'
        ];
        $request = Request::initInstance($server, [], [], [], [], [], []);
        $repo = new UserRepository();
        $httpAuth = new AuthHttpMiddleware($repo);
        $user =  $httpAuth->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(1, $user->getId());
        $this->assertEquals('John', $user->getFirstname());
        $this->assertTrue($httpAuth->isAuth());
        $this->assertEquals($roles, $user->getRoles());
    }
}

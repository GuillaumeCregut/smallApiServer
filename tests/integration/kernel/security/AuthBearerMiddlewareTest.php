<?php

use App\Security\User;
use App\Kernel\Request;
use App\Kernel\GetEnvDatas;
use PHPUnit\Framework\TestCase;
use App\Security\UserRepository;
use App\Kernel\Security\JwtToken;
use App\Kernel\Connector\DatabaseException;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Interfaces\Databases\ConnectorInterface;
use App\Kernel\Middleware\Security\AuthBearerMiddleware;

class AuthBearerMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Request::resetInstance();
        ConnectorDispatcher::resetConnector();
        GetEnvDatas::resetInstance();
    }

    public function testNoToken(): void
    {
        $filename = GetEnvDatas::getAppPath() . DIRECTORY_SEPARATOR . '.env';
        $env = GetEnvDatas::getEnvInstance($filename);
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $headers = [];
        $request = Request::initInstance([], [], [], [], [], [], $headers);
        $repo = new UserRepository();
        $bearerAuth = new AuthBearerMiddleware($repo);
        $this->assertNull($bearerAuth->getUser());
        $this->assertFalse($bearerAuth->isAuth());
    }

    public function testWithBadToken(): void
    {
        $filename = __DIR__ . DIRECTORY_SEPARATOR . '.env.sample';
        $env = GetEnvDatas::getEnvInstance($filename);
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn([]);
        ConnectorDispatcher::setConnector($connector);
        $headers = [
            'Authorization' => 'Bearer YOUR_TOKEN_HERE'
        ];
        $request = Request::initInstance([], [], [], [], [], [], $headers);
        $repo = new UserRepository();
        $bearerAuth = new AuthBearerMiddleware($repo);
        $this->assertNull($bearerAuth->getUser());
        $this->assertFalse($bearerAuth->isAuth());
    }

    public function testWithDbError(): void
    {
        $jwt = new JwtToken();
        $payload = [
            'userName' => 'toto',
            'userId'=> 1
        ];
        $secret = 'your_secret';
        $newToken = $jwt->createToken($payload, $secret);
        $filename = __DIR__ . DIRECTORY_SEPARATOR . '.env.sample';
        $env = GetEnvDatas::getEnvInstance($filename);
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn([
                'id' => 1,
                'name' => 'Doe',
                'first_name' => 'John',
                'age' => 30
            ]);
        ConnectorDispatcher::setConnector($connector);
        $headers = [
            'Authorization' => 'Bearer ' . $newToken
        ];
        $request = Request::initInstance([], [], [], [], [], [], $headers);
        $repo = new UserRepository();
        $this->expectException(DatabaseException::class);
        $bearerAuth = new AuthBearerMiddleware($repo);
        $user = $bearerAuth->getUser();
        $this->assertNull($user);
    }

    public function testWithKnownId(): void
    {
        $jwt = new JwtToken();
        $payload = [
            'userName' => 'toto',
            'userId' => 1
        ];
        $secret = 'your_secret';
        $newToken = $jwt->createToken($payload, $secret);
        $filename = __DIR__ . DIRECTORY_SEPARATOR . '.env.sample';
        $env = GetEnvDatas::getEnvInstance($filename);
        $connector = $this->createStub(ConnectorInterface::class);
        $roles = ['USER'];
        $role = json_encode($roles);
        $connector->method('fetchQuery')
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'Doe',
                    'firstname' => 'John',
                    'password' => '$2y$12$5a6OfknvekRlqMQSkQ8pFO04Lldfr8htT34os6bnafDUvwOCeJ4u2',
                    'roles' => $role,
                    'token' => $newToken
                ]
            ]);
        ConnectorDispatcher::setConnector($connector);
        $headers = [
            'Authorization' => 'Bearer ' . $newToken
        ];
        $request = Request::initInstance([], [], [], [], [], [], $headers);
        $repo = new UserRepository();
        $sessionAuth = new AuthBearerMiddleware($repo);
        $user =  $sessionAuth->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(1, $user->getId());
        $this->assertEquals('John', $user->getFirstname());
        $this->assertTrue($sessionAuth->isAuth());
        $this->assertEquals($roles, $user->getRoles());
        $this->assertNull($user->getPassword());
    }
}

<?php

use App\Controllers\UserController;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Connector\MySQLConnector;
use App\Kernel\GetEnvDatas;
use App\Kernel\Request;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private array $datas = [];
    private array $files = [];
    private array $session = [];
    private array $headers = [];
    private array $cookies = [];
    private static int $userId = 0;
    protected function setUp(): void
    {
        parent::setUp();
        Request::resetInstance();
        ConnectorDispatcher::resetConnector();
        GetEnvDatas::resetInstance();
    }

     public function testAddUser(): void
    {
        $envFile = GetEnvDatas::getAppPath() . DIRECTORY_SEPARATOR . '.env';
        GetEnvDatas::getEnvInstance($envFile);
        $envs = GetEnvDatas::getEnvInstance()->getDdCredentials();
        $connector = MySQLConnector::getInstance($envs);
        ConnectorDispatcher::setConnector($connector);
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/user',
            'HTTP_REFERER' => '',
            'HTTP_HOST' => 'localhost:9000',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ];
        $get = [];
        $post = [
            'name' =>'London',
            'firstname' => 'Jack',
            'password' => '1234',
            'username' =>'jlondon'
        ];
        $request = Request::initInstance($server, $this->datas, $get, $post, $this->files, $this->session, $this->headers, $this->cookies);
        $controller = new UserController();
        $response = $controller->add();
        $result = $response->getBody();
        $data = json_decode($result,true);
        $code = $response->getStatusCode();
        $this->assertEquals(201, $code);
        $this->assertIsArray($data);
        self::$userId = $data['id'];
    }

    public function testGetAll(): void
    {
        $envFile = GetEnvDatas::getAppPath() . DIRECTORY_SEPARATOR . '.env';
        GetEnvDatas::getEnvInstance($envFile);
        $envs = GetEnvDatas::getEnvInstance()->getDdCredentials();
        $connector = MySQLConnector::getInstance($envs);
        ConnectorDispatcher::setConnector($connector);
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/local/index?id=123&name=john',
            'HTTP_REFERER' => '',
            'HTTP_HOST' => 'localhost:8000',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ];
        $get = [];
        $post = [];
        $request = Request::initInstance($server, $this->datas, $get, $post, $this->files, $this->session, $this->headers, $this->cookies);
        $controller = new UserController();
        $response = $controller->get();
        $result = $response->getBody();
        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded);
        $this->assertGreaterThan(0, count($decoded));
    }
    public function testGetNotFound(): void
    {
        $envFile = GetEnvDatas::getAppPath() . DIRECTORY_SEPARATOR . '.env';
        GetEnvDatas::getEnvInstance($envFile);
        $envs = GetEnvDatas::getEnvInstance()->getDdCredentials();
        $connector = MySQLConnector::getInstance($envs);
        ConnectorDispatcher::setConnector($connector);
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/local/index?id=123&name=john',
            'HTTP_REFERER' => 'https://google.com',
            'HTTP_HOST' => 'localhost:8000',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ];
        $get = ['id'=>-1];
        $post = [];
        $request = Request::initInstance($server, $this->datas, $get, $post, $this->files, $this->session, $this->headers, $this->cookies);
        $request->getURI();
        $controller = new UserController();
        $response = $controller->get();
        $result = $response->getBody();
        $this->assertEquals("404 - Page not found", $result);
        $code = $response->getStatusCode();
        $this->assertEquals(404, $code);
    }

    #[Depends('testAddUser')] 
    public function testGetFound(): void
    {
        $envFile = GetEnvDatas::getAppPath() . DIRECTORY_SEPARATOR . '.env';
        GetEnvDatas::getEnvInstance($envFile);
        $envs = GetEnvDatas::getEnvInstance()->getDdCredentials();
        $connector = MySQLConnector::getInstance($envs);
        ConnectorDispatcher::setConnector($connector);
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/local/index?id=123&name=john',
            'HTTP_REFERER' => 'https://google.com',
            'HTTP_HOST' => 'localhost:8000',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ];
        $get = ['id'=>self::$userId];
        $post = [];
        $request = Request::initInstance($server, $this->datas, $get, $post, $this->files, $this->session, $this->headers, $this->cookies);
        $request->getURI();
        $controller = new UserController();
        $response = $controller->get();
        $result = $response->getBody();
        $data = json_decode($result,true);
        $code = $response->getStatusCode();
        $this->assertEquals(200, $code);
        $this->assertIsArray($data);
    }
   
    #[Depends('testGetFound')] 
    public function testUpdateUser(): void
    {
        $envFile = GetEnvDatas::getAppPath() . DIRECTORY_SEPARATOR . '.env';
        GetEnvDatas::getEnvInstance($envFile);
        $envs = GetEnvDatas::getEnvInstance()->getDdCredentials();
        $connector = MySQLConnector::getInstance($envs);
        ConnectorDispatcher::setConnector($connector);
        $server = [
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/local/index?id=123&name=john',
            'HTTP_REFERER' => 'https://google.com',
            'HTTP_HOST' => 'localhost:8000',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ];
        $get = [];
        $post = [
            'id' => self::$userId,
            'name' =>'London',
            'firstname' => 'Jack',
            'password' => '1234',
            'username' =>'test'
        ];
        $request = Request::initInstance($server, $this->datas, $get, $post, $this->files, $this->session, $this->headers, $this->cookies);
        $controller = new UserController();
        $response = $controller->update();
        $code = $response->getStatusCode();
        $this->assertEquals(204, $code);
    }

    #[Depends('testUpdateUser')]    
    public function testDeleteUser(): void
    {
        $envFile = GetEnvDatas::getAppPath() . DIRECTORY_SEPARATOR . '.env';
        GetEnvDatas::getEnvInstance($envFile);
        $envs = GetEnvDatas::getEnvInstance()->getDdCredentials();
        $connector = MySQLConnector::getInstance($envs);
        ConnectorDispatcher::setConnector($connector);
        $server = [
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/local/user/8',
            'HTTP_REFERER' => 'https://google.com',
            'HTTP_HOST' => 'localhost:8000',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ];
        $get = [];
        $post =['id' => self::$userId];
        $request = Request::initInstance($server, $this->datas, $get, $post, $this->files, $this->session, $this->headers, $this->cookies);
        $controller = new UserController();
        $response = $controller->delete();
        $result = $response->getBody();
        $code = $response->getStatusCode();
        $this->assertEquals(204, $code);
        $response = $controller->get();
        $result = $response->getBody();
        $this->assertEquals("404 - Page not found", $result);
        $code = $response->getStatusCode();
        $this->assertEquals(404, $code);
    }
}

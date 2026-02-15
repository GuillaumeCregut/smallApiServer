<?php

use App\Controllers\UserController;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Connector\MySQLConnector;
use App\Kernel\GetEnvDatas;
use App\Kernel\Request;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private array $datas = [];
    private array $files = [];
    private array $session = [];
    private array $headers = [];
    private array $cookies = [];

    protected function setUp(): void
    {
        parent::setUp();
        Request::resetInstance();
        ConnectorDispatcher::resetConnector();
        GetEnvDatas::resetInstance();
    }

    public function testSetup(): void
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
        $get = [];
        $post = [];
        $request = Request::initInstance($server, $this->datas, $get, $post, $this->files, $this->session, $this->headers, $this->cookies);
        $controller = new UserController();
        $response = $controller->get();
        $this->assertTrue(true);
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
            'HTTP_REFERER' => 'https://google.com',
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

    public function testGetFound(): void
    {
        /* Id depends on your own database */
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
        $get = ['id'=>6];
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

     public function testAddUser(): void
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
    }
}

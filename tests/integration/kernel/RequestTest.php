<?php

use App\Kernel\Files\FileUpload;
use App\Kernel\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    //Test création instance de request
    public function testInitRequest(): void
    {
        Request::resetInstance();
        $request = Request::initInstance([], [], [], [], [], [], []);
        $this->assertIsObject($request);
        $this->assertInstanceOf(Request::class, $request);
    }

    public function testRequestHasServerInformations(): void
    {
        Request::resetInstance();
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/local/index?id=123&name=john',
            'HTTP_REFERER' => 'https://google.com',
            'HTTP_HOST' => 'localhost:8000',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ];
        $request = Request::initInstance($server, [], [], [], [], [], []);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('HTTP/1.1', $request->getServer('SERVER_PROTOCOL'));
        $this->assertFalse($request->isRefererValid());
        $this->assertEquals('local/index', $request->getURI());
        $this->assertIsArray($request->getAllDatas());
        $this->assertEmpty($request->getAllDatas());
    }

    public function testRequestHasDatas(): void
    {
        Request::resetInstance();
        $server = [
            'HTTP_REFERER' => 'localhost:8000',
            'HTTP_HOST' => 'localhost:8000',
        ];
        $datas = [
            'name' => 'Mike',
            'lastname' => 'Doe',
            'id' => 2
        ];
        $get = [
            'post' => 3,
            'id' => 4
        ];
        $post = [
            'email' => 'test@test',
            'id' => 1
        ];
        $request = Request::initInstance($server, $datas, $get, $post, [], [], []);
        $this->assertIsArray($request->getAllDatas());
        $this->assertEquals('test@test', $request->getData('email'));
        $this->assertEquals(3, $request->getData('post'));
        $this->assertEquals('Doe', $request->getData('lastname'));
        $this->assertTrue($request->isRefererValid());
        $this->assertEquals(2, $request->getData('id'));
    }

    public function testRequestHasIdFromUrl()
    {
        Request::resetInstance();
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/local/index/1',
            'HTTP_REFERER' => 'https://google.com',
            'HTTP_HOST' => 'localhost:8000',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ];
        $request = Request::initInstance($server, [], [], [], [], [], []);
        $this->assertEquals('local/index', $request->getURI());
        $this->assertEquals(1, $request->getData('id'));
    }

    public function testSetRequestData(): void
    {
        Request::resetInstance();
        $server = [
            'HTTP_REFERER' => 'localhost:8000',
            'HTTP_HOST' => 'localhost:8000',
        ];
        $datas = [
            'name' => 'Mike',
            'lastname' => 'Doe',
            'id' => 2
        ];
        $get = [
            'post' => 3,
            'id' => 4
        ];
        $post = [
            'email' => 'test@test',
            'id' => 1
        ];
        $request = Request::initInstance($server, [], [], [], [], [], []);
        $this->assertArrayNotHasKey('param', $request->getAllDatas());
        $request->setData('param', 10);
        $this->assertArrayHasKey('param', $request->getAllDatas());
        $this->assertEquals(10, $request->getData('param'));
    }

    public function testRequestInstance(): void
    {
        Request::resetInstance();
        $server = [
            'HTTP_REFERER' => 'localhost:8000',
            'HTTP_HOST' => 'localhost:8000',
        ];
        $datas = [
            'name' => 'Mike',
            'lastname' => 'Doe',
            'id' => 2
        ];
        $get = [
            'post' => 3,
            'id' => 4
        ];
        $post = [
            'email' => 'test@test',
            'id' => 1
        ];
        $request = Request::initInstance($server, [], [], [], [], [], []);
        $request2 = Request::getRequestInstance();
        $this->assertSame($request, $request2);
    }

    public function testRequestHasFile(): void
    {
        Request::resetInstance();
       $files = [
            'documents' => [
                'name' => 'file2.pdf',
                'type' => 'application/pdf',
                'tmp_name' =>  '/tmp/phpYzdqkE',
                'error' => 0,
                'size' => 12345,
                'full_path' => 'toto'
            ],
             'titi' => [
                'name' => 'file2.pdf',
                'type' => 'application/pdf',
                'tmp_name' =>  '/tmp/phpYzdqkE',
                'error' => 0,
                'size' => 12345,
                'full_path' => 'toto'
            ],
        ];
        $request = Request::initInstance([], [], [], [], $files, [], []);
        $reqFiles = $request->getFiles();
        $this->assertIsArray($reqFiles);
        $this->assertArrayHasKey('documents',$reqFiles);
        $this->assertArrayNotHasKey('titp',$reqFiles);
        $file1=$request->getFile('documents')[0];
        $this->assertInstanceOf(FileUpload::class,$file1); 
        $file2 = $request->getFile('documen');
        $this->assertNull($file2);
    }

    public function RequestHasSession(): void
    {
        Request::resetInstance();
         $request = Request::initInstance([], [], [], [], [], [], []);
         $this->assertNull($request->getSessionValue('test'));
         $request->setSessionValue('test', 'toto');
         $this->assertEquals('toto', $request->getSessionValue('test'));
    }

    public function testRequestHasHeaders(): void
    {
        Request::resetInstance();
        $headers =[
            'pragma'=>'no-cache'
        ];
        $request = Request::initInstance([], [], [], [], [], [], $headers);
        $this->assertEquals('no-cache', $request->getHeaders('pragma'));
    }

     public function testRequestHaCookies(): void
    {
        Request::resetInstance();
        $cookies =[
            'pragma'=>'no-cache'
        ];
        $request = Request::initInstance([], [], [], [], [], [], [], $cookies);
        $this->assertEquals('no-cache', $request->getCookie('pragma'));
    }

     public function testUserParam(): void
    {
        Request::resetInstance();
        $request = Request::initInstance([], [], [], [], [], [], []);
        $request->addParam('name', 'john');
        $this->assertEquals('john', $request->getParam('name'));
    }
}

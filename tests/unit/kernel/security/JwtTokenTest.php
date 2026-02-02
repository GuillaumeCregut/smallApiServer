<?php

use App\Kernel\Security\JwtToken;
use PHPUnit\Framework\TestCase;

class JwtTokenTest extends TestCase
{
    public function testCheckToken(): void
    {
        $jwt = new JwtToken();
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwiaWF0IjoxNzY5OTUzMDgzLCJleHAiOjE3NzAwMzk0ODN9.GI5Sx4aNEH6GKneyo5cKzBwGX9mx-ndDO9sDghNbuSk';
        $secret = 'secret';
        $result = $jwt->checkToken($token, $secret);
        $this->assertTrue($result);
    }

    public function testCreateToken(): void
    {
        $payload = [
            'userId' => 1
        ];
        $secret = 'secret';
        $jwt = new JwtToken();
        $newToken = $jwt->createToken($payload, $secret);
        $this->assertTrue($jwt->checkToken($newToken, $secret));
    }

     public function testStaticCreateToken(): void
    {
        $payload = [
            'userId' => 1
        ];
        $secret = 'secret';
        $jwt2 = new JwtToken();
        $newToken = JwtToken::createToken($payload, $secret);
        $this->assertTrue($jwt2->checkToken($newToken, $secret));
    }

    public function testCheckFormatToken(): void
    {
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwiaWF0IjoxNzY5OTUzMDgzLCJleHAiOjE3NzAwMzk0ODN9.GI5Sx4aNEH6GKneyo5cKzBwGX9mx-ndDO9sDghNbuSk';
        $jwt = new JwtToken();
        $this->assertTrue($jwt->checkFormat($token));
    }

    public function testTokenWithWrongFormat(): void
    {
        $token = 'yJpZCI6MSwiaWF0IjoxNzY5OTUzMDgzLCJleHAiOjE3NzAwMzk0ODN9.GI5Sx4aNEH6GKneyo5cKzBwGX9mx-ndDO9sDghNbuSk';
        $jwt = new JwtToken();
        $this->assertFalse($jwt->checkFormat($token));
    }

    public function testIsExpiredToken(): void
    {
        $jwt = new JwtToken();
        $payload = [
            'userId' => 1
        ];
        $secret = 'secret';
        $newToken = $jwt->createToken($payload, $secret);
        $this->assertFalse($jwt->isExpired($newToken));
        $newToken = $jwt->createToken($payload, $secret,1);
        sleep(5);
        $this->assertTrue($jwt->isExpired($newToken));
    }

    public function testWrongFormatToken(): void
    {
        $token = 'yJpZCI6MSwiaWF0IjoxNzY5OTUzMDgzLCJleHAiOjE3NzAwMzk0ODN9.GI5Sx4aNEH6GKneyo5cKzBwGX9mx-ndDO9sDghNbuSk';
        $jwt = new JwtToken();
        $this->assertFalse($jwt->checkToken($token,'secret'));
        $this->assertTrue($jwt->isExpired($token));
    }

    public function testExceptionExtractPayloadWithWrongToken(): void
    {
        $token = 'yJpZCI6MSwiaWF0IjoxNzY5OTUzMDgzLCJleHAiOjE3NzAwMzk0ODN9.GI5Sx4aNEH6GKneyo5cKzBwGX9mx-ndDO9sDghNbuSk';
        $jwt = new JwtToken();
        $this->expectException(Exception::class);
        $jwt->extractPayload($token);
    }

    public function testExtractPayload(): void
    {
        $jwt = new JwtToken();
        $payload = [
            'userName' => 'toto',
            'user_id'=> 1
        ];
        $secret = 'secret';
        $newToken = $jwt->createToken($payload, $secret);
        $result = $jwt->extractPayload($newToken);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('userName', $result);
        $this->assertSame('toto', $result['userName']);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertSame(1, $result['user_id']);
    }

    public function testGetPayload(): void
    {
         $jwt = new JwtToken();
        $payload = [
            'userName' => 'toto',
        ];
        $secret = 'secret';
        $newToken = $jwt->createToken($payload, $secret);
        $this->assertNull($jwt->getPayload());
        $result = $jwt->checkToken($newToken, $secret);
        $this->assertArrayHasKey('userName',$jwt->getPayload());
    }

}

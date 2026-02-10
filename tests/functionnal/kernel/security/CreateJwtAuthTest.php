<?php

use App\Kernel\GetEnvDatas;
use PHPUnit\Framework\TestCase;
use App\Kernel\Security\JwtToken;
use App\Kernel\Security\CreateJwtAuth;

class CreateJwtAuthTest extends TestCase
{
    public function testCreateAuthJwt()
    {
        GetEnvDatas::resetInstance();
        $filename = __DIR__ . DIRECTORY_SEPARATOR . '.env.sample';
        $env = GetEnvDatas::getEnvInstance($filename);
        $payload = [
            'userId' => 1,
            'role' => ['admin'],
            'firstname' => 'John',
            'lastname' => 'Doe'
        ];
        $token = CreateJwtAuth::createToken(1,['admin'], 'John', 'Doe', 86400);
        $this->assertTrue(JwtToken::checkFormat($token));
        $jwt = new JwtToken();
        $tokenPayload = $jwt->extractPayload($token);
        $this->assertIsArray($tokenPayload);
        $this->assertArrayHasKey('userId', $tokenPayload);
        $this->assertEquals(1, $tokenPayload['userId']);
        $this->assertIsArray($tokenPayload['role']);
        $this->assertEquals('admin', $tokenPayload['role'][0]);
    }
}

<?php

use App\Kernel\Security\CreateJwtAuth;
use App\Kernel\Security\JwtToken;
use PHPUnit\Framework\TestCase;

class CreateJwtAuthTest extends TestCase
{
    public function testCreateAuthJwt()
    {
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

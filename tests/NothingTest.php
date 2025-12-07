<?php

use PHPUnit\Framework\TestCase;

class NothingTest extends TestCase
{
    public function testNothing()
    {
        $this->assertTrue(true);
    }

    public function testAuth()
    {
        $this->expectException(InvalidArgumentException::class);
        $auth = new App\Middleware\AuthBearerMiddleware();
        $this->assertFalse($auth->isAuth('111'));
    }
}
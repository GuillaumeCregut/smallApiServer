<?php

use App\Kernel\GetEnvDatas;
use PHPUnit\Framework\TestCase;
use App\Kernel\Exceptions\KernelException;

class GetEnvDatasTest extends TestCase
{

    public function testGetAppPath(): void
    {
        $path = GetEnvDatas::getAppPath();
        $pathTest = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR;
        $this->assertEquals($pathTest, $path);
    }

    public function testLoadEnvErrorFile(): void
    {
        GetEnvDatas::resetInstance();
        $this->expectException(KernelException::class);
        $this->expectExceptionMessage('Env not initialized correctly');
        $temp = GetEnvDatas::getEnvInstance();
    }

    public function testLoadEnvMisformedFile(): void
    {
        GetEnvDatas::resetInstance();
        $filename = __DIR__ . DIRECTORY_SEPARATOR . 'env2.sample';
        $this->expectException(KernelException::class);
        $this->expectExceptionMessage('Error loading env File');
        $temp = GetEnvDatas::getEnvInstance($filename);
    }


    public function testLoadFalseEnvFile(): void
    {
        GetEnvDatas::resetInstance();
        $filename = 'test.env';
        $this->expectException(KernelException::class);
        $temp = GetEnvDatas::getEnvInstance($filename);
    }

    public function testLoadEnvFileValues(): void
    {
        GetEnvDatas::resetInstance();
        $filename = __DIR__ . DIRECTORY_SEPARATOR . '.env.sample';
        $env = GetEnvDatas::getEnvInstance($filename);
        $this->assertEquals('your_secret', $env->get('JWT_SECRET'));
        $env2 = GetEnvDatas::getEnvInstance();
        $this->assertEquals('your_secret', $env2->get('JWT_SECRET'));
    }

    public function testLoadDBCredentials(): void
    {
        GetEnvDatas::resetInstance();
        $filename = __DIR__ . DIRECTORY_SEPARATOR . '.env.sample';
        $env = GetEnvDatas::getEnvInstance($filename)->getDdCredentials();
        $this->assertArrayHasKey('DB_HOST',$env);
        $this->assertArrayHasKey('DB_NAME',$env);
        $this->assertArrayHasKey('DB_USER',$env);
        $this->assertArrayHasKey('DB_PASS',$env);

    }
}

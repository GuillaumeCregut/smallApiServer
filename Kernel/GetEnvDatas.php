<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel;

use App\Kernel\Exceptions\KernelException;
use Exception;

class GetEnvDatas
{
    private array $envVar = [];
    private static ?GetEnvDatas $instance = null;

    public function get(string $key, $default = null): mixed
    {
        if (key_exists($key, $this->envVar)) {
            return  $this->envVar[$key];
        } else {
            return  $default;
        }
    }

    public function getAll(): array
    {
        return $this->envVar;
    }

    public function getDdCredentials(): array
    {
        return [
            'host' => $this->envVar['host'],
            'db' =>  $this->envVar['db'],
            'user' =>  $this->envVar['user'],
            'pass' =>  $this->envVar['pass'],
        ];
    }

    public static function getEnvInstance(?string $envFile = null): GetEnvDatas
    {
        if ((null === $envFile) && (null === self::$instance)) {
            throw new KernelException('Env not initialized correctly');
        }
        if ((null === self::$instance) && !file_exists($envFile)) {
            throw new KernelException('Env file not found : ' . $envFile);
        }
        if (is_null(self::$instance)) {
            self::$instance = new GetEnvDatas($envFile);
        }
        return self::$instance;
    }

    public static function getAppPath(): string
    {
        $appPath =  dirname(__DIR__, 1);
        return rtrim($appPath, '/\\') . DIRECTORY_SEPARATOR;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    private function __construct(string $iniFile)
    {
        //$iniFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        $envs = parse_ini_file($iniFile, false);
        if (!$envs) {
            throw new KernelException('Error loading env File');
        }
        $this->envVar = $envs;
    }
}

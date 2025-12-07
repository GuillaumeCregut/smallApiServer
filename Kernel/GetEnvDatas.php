<?php

namespace App\Kernel;

class GetEnvDatas
{
    private array $envVar = [];
    private static ?GetEnvDatas $instance = null;

    public function __construct()
    {
        $iniFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (file_exists($iniFile)) {
            $envs = parse_ini_file($iniFile, false);
            $this->envVar['host'] = $envs['host'] ?? 'localhost';
            $this->envVar['db'] = $envs['db'] ?? 'attaquant';
            $this->envVar['user'] = $envs['user'] ?? 'root';
            $this->envVar['pass'] = $envs['pass'] ?? '';
            $this->envVar['secret'] = $envs['secret'] ?? '';
            $this->envVar['maxsize'] = $envs['secret'] ?? '';

        } else {
            // You can choose to throw an exception or handle the error as needed
            throw new \Exception("Configuration file not found: " . $iniFile);
        }
    }
    public function get(string $key, $default = null): mixed
    {
        if(key_exists($key,$this->envVar)){
            return  $this->envVar[$key];
        } else {
            return  $default;
        }
    }

    public static function getEnvInstance(): GetEnvDatas
    {
        if (is_null(self::$instance)) {
            self::$instance = new GetEnvDatas();
        }
        return self::$instance;
    }
}

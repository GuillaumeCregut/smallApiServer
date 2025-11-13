<?php

namespace App\Services;

use App\Interfaces\ConnectorInterface;

class Connector implements ConnectorInterface
{
    private \PDO $pdo;

    public function __construct()
    {
        $charset = 'utf8mb4';
        $iniFile = dirname(__DIR__) . DIRECTORY_SEPARATOR .'.env';
        if (file_exists($iniFile)) {
            $envs = parse_ini_file($iniFile, false);
            $host = $envs['host'] ?? 'localhost';
            $db = $envs['db'] ?? 'attaquant';
            $user = $envs['user'] ?? 'root';
            $pass = $envs['pass'] ?? '';
        } else {
            // You can choose to throw an exception or handle the error as needed
            throw new \Exception("Configuration file not found: " . $iniFile);
        }
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new \PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function getConnection(): \PDO
    {
        return $this->pdo;
    }
}
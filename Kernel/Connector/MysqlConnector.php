<?php

namespace App\Kernel\Connector;

use Exception;
use App\Kernel\GetEnvDatas;
use App\Kernel\Interfaces\Databases\ConnectorInterface;

class MySQLConnector implements ConnectorInterface
{
    private \PDO $pdo;
    private static ?MysqlConnector $instance=null;

    public static function getInstance(): ConnectorInterface
    {
        if (null === self::$instance) {
            self::$instance = new MySQLConnector();
        }
        return self::$instance;
    }
    public function __construct()
    {
        $charset = 'utf8mb4';
        $envs= GetEnvDatas::getEnvInstance();
        try{

            $host = $envs->get('host');
            $db = $envs->get('db');
            $user = $envs->get('user');
            $pass = $envs->get('pass');
           
            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $options = [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ];
        } catch(Exception $e) {
            throw new Exception('Env datas does not exist');
        }

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
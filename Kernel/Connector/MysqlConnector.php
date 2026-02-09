<?php

namespace App\Kernel\Connector;

use PDO;
use Exception;
use PDOException;
use App\Kernel\GetEnvDatas;
use App\Kernel\Interfaces\Databases\ConnectorInterface;

class MySQLConnector implements ConnectorInterface
{
    private \PDO $pdo;
    private static ?MysqlConnector $instance = null;

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
        $envs = GetEnvDatas::getEnvInstance();
        try {
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
        } catch (Exception $e) {
            throw new Exception('Env datas does not exist');
        }
        try {
            $this->pdo = new \PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function getConnection(): \PDO
    {
        return $this->pdo;
    }

    public function startTransac(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commitTransc(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }
    public function executeQuery(string $sql, array $params = []): bool | int
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            if(!$result) {
                return $result;
            } 
            return (int) $this->pdo->lastInsertId();
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }
    }
    public function fetchQuery(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }
    }

    public function FetchQueryOnce(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }
    }
}

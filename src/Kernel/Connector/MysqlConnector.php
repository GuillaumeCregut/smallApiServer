<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector;

use App\Kernel\Exceptions\KernelException;
use PDO;
use Exception;
use PDOException;
use App\Kernel\Interfaces\Databases\ConnectorInterface;
use App\Kernel\Connector\DatabaseException;

class MySQLConnector implements ConnectorInterface
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 1000;
    private ?PDO $pdo = null;
    private static ?MysqlConnector $instance = null;
    private array $credentials = [];

    public static function getInstance(?array $env = null): ConnectorInterface
    {
        if ((null === self::$instance) && (null === $env)) {
            throw new KernelException('Database not initilized');
        }
        if (null === self::$instance) {
            self::$instance = new MySQLConnector($env);
        }
        return self::$instance;
    }

    public function getConnection(): \PDO
    {
        return $this->pdo;
    }

    public function startTransac(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commitTransac(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }
    public function executeQuery(string $sql, array $params = []): int | bool
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            if (stripos(trim($sql), 'INSERT') === 0) {
                return (int) $this->pdo->lastInsertId();
            }
            $affectedRows = $stmt->rowCount();
            if ($affectedRows > 0) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage(), (int)$e->getCode());
        }
    }
    public function fetchQuery(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function fetchQueryOnce(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    private function __construct($envs)
    {
        if (
            !array_key_exists('DB_HOST', $envs) ||
            !array_key_exists('DB_NAME', $envs) ||
            !array_key_exists('DB_USER', $envs) ||
            !array_key_exists('DB_PASS', $envs)
        ) {
            throw new KernelException('Env datas does not exist');
        }
        $this->credentials = [
            'host' => $envs['DB_HOST'],
            'db' => $envs['DB_NAME'],
            'user' => $envs['DB_USER'],
            'pass' => $envs['DB_PASS'],
            'port' => $envs['DB_PORT'] ?? 3306
        ];
        $this->connect();
    }

    private function connect(): void
    {
        $attempt = 0;
        $lastException = null;
        $host = $this->credentials['host'];
        $db = $this->credentials['db'];
        $user = $this->credentials['user'];
        $pass = $this->credentials['pass'];
        $port = $this->credentials['port'];
        $charset = 'utf8mb4';
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        while ($attempt < self::MAX_RETRIES) {
            try {
                $this->pdo = new \PDO($dsn, $user, $pass, $options);
                return;
            } catch (PDOException $e) {
                $lastException = $e;
                $attempt++;
                $stopConnexion = $this->stopCode($e);
                if ($stopConnexion) {
                    throw new DatabaseException($e->getMessage(), $e->getCode());
                }
                if ($attempt < self::MAX_RETRIES) {
                    $delayMs = self::RETRY_DELAY_MS * pow(2, $attempt - 1);
                    usleep($delayMs * 1000);
                }
            }
        }
        throw new DatabaseException(
            sprintf(
                'Failed to connect to database after %d attempts. Last error: %s',
                self::MAX_RETRIES,
                $lastException->getMessage()
            ),
            (int)$lastException->getCode()
        );
    }

    private function stopCode(PDOException $e): bool
    {
        $codes = [
            1044, //Access denied for user to database
            1045, // Access denied for user 'username'@'hostname' (using password: YES)
            1049, //DB inconnue
            //  2002, //Can't connect to local MySQL server through socket
            1130, // Host is not allowed to connect
            //   2003, //Can't connect to MySQL server on '[host]'
        ];
        if (in_array($e->getCode(), $codes)) {
            return true;
        }
        return false;
    }
}

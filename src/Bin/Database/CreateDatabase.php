<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Bin\Database;

use App\Bin\ConsoleHelper;
use Exception;
use App\Kernel\Config\DatabaseConnector;
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\GetEnvDatas;

class CreateDatabase
{
    private ConnectorInterface $connector;

    public function __construct()
    {
        try {
            $this->connector = DatabaseConnector::getDetachedConnector();
        } catch (Exception $e) {
            $error = ConsoleHelper::makeSpecial('error', 'red', 'bold');
            echo "{$error} : Database connection error. Abort\n";
        }
    }

    public function execute(): void
    {
        $dbName = GetEnvDatas::getEnvInstance()->getDdCredentials()['DB_NAME'];

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
            $error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
            echo "{$error} database name {$dbName} is not correct. Aborting\n";
            return;
        }

        if ($this->checkDbExists($dbName)) {
            echo "Database {$dbName} already exists. Aborting\n";
        }

        $response = ConsoleHelper::askWhile("Do you want to create database {$dbName} ? (y/n)", ['y', 'n']);
        if ('n' === $response) {
            echo "Aborting.\n";
            return;
        }

        $query = $this->connector->getCreateDatabaseQuery($dbName);
        echo "Creating database {$dbName}.\n";
        try {
            $created = $this->connector->executeQuery($query, []);
            if (! $created) {
                $error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
                echo "{$error} while executing creation database name {$dbName}. Aborting\n";
                return;
            }
            if ($this->checkDbExists($dbName)) {
                $message = ConsoleHelper::makeSpecial("Database {$dbName} created successfully", 'green', 'bold');
                echo "{$message}.\n";
            } else {
                $error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
                echo "{$error} database {$dbName} not created\n";
                return;
            }
        } catch (Exception $e) {
            $error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
            echo "{$error} : {$e->getMessage()}\n";
        }
    }

    private function getAllDatabases(): array
    {
        $sql = 'SHOW DATABASES';
        $dbList = $this->connector->fetchQuery($sql, []);
        $databases = array_map(fn($row) => current($row), $dbList);
        return $databases;
    }

    private function checkDbExists(string $name): bool
    {
        $existingDb = $this->getAllDatabases();
        return in_array($name, $existingDb);
    }
}

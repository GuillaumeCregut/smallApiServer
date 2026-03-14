<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils\Migration;

use Exception;
use App\Kernel\Exceptions\KernelException;
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Connector\Interfaces\MigrationInterface;

class MigrationRunner
{
    private const MIGRATIONS_TABLE = 'migrations';
    private string $migrationsPath;

    public function __construct(
        private ConnectorInterface $connector,
        string $rootPath
    ) {
        $this->migrationsPath = rtrim($rootPath, '/') . '/migrations';
        $this->ensureMigrationsTable();
    }

    public function migrate(): array
    {
        $pending = $this->getPendingMigrations();
 
        if (empty($pending)) {
            return [];
        }
 
        $executed = [];
        foreach ($pending as $version => $filePath) {
            $migration = $this->loadMigration($filePath);
            $this->connector->startTransac();
            try {
                $migration->up($this->connector);
                $this->markAsExecuted($version);
                $this->connector->commitTransac();
                $executed[] = (string)$version;
            } catch (Exception $e) {
                $this->connector->rollBack();
                throw new KernelException("Migration {$version} failed: " . $e->getMessage());
            }
        }
 
        return $executed;
    }

    /**
     * Rollback the last executed migration (down).
     * Returns the rolled back version, or null if nothing to rollback.
     */
    public function rollback(): ?string
    {
        $last = $this->getLastExecutedMigration();
 
        if (null === $last) {
            return null;
        }
 
        $filePath  = "{$this->migrationsPath}/Version{$last}.php";
        $migration = $this->loadMigration($filePath);
 
        $this->connector->startTransac();
        try {
            $migration->down($this->connector);
            $this->markAsRolledBack($last);
            $this->connector->commitTransac();
        } catch (\Exception $e) {
            $this->connector->rollBack();
            throw new KernelException("Rollback of {$last} failed: " . $e->getMessage());
        }
 
        return $last;
    }

     public function status(): array
    {
        $all      = $this->getAllMigrationFiles();
        $executed = $this->getExecutedVersions();
        $status   = [];
 
        foreach ($all as $version => $filePath) {
            $status[$version] = in_array($version, $executed) ? 'executed' : 'pending';
        }
 
        return $status;
    }

    /**
     * Create the migrations tracking table if it doesn't exist.
     */
    private function ensureMigrationsTable(): void
    {
        $this->connector->executeQuery("
            CREATE TABLE IF NOT EXISTS `" . self::MIGRATIONS_TABLE . "` (
                `version`     VARCHAR(20)  NOT NULL,
                `executed_at` DATETIME     NOT NULL,
                PRIMARY KEY (`version`)
            )
        ");
    }

    /**
     * Returns pending migrations as [version => filePath], ordered by version (asc).
     */
    private function getPendingMigrations(): array
    {
        $all      = $this->getAllMigrationFiles();
        $executed = $this->getExecutedVersions();
        $pending  = [];
 
        foreach ($all as $version => $filePath) {
            if (!in_array($version, $executed)) {
                $pending[(string)$version] = $filePath;
            }
        }
 
        ksort($pending, SORT_STRING);
        return $pending;
    }

    /**
     * Scans migrations directory and returns [version => filePath].
     */
    private function getAllMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }
 
        $files  = glob("{$this->migrationsPath}/Version*.php");
        $result = [];
 
        foreach ($files as $filePath) {
            $className = basename($filePath, '.php');
            $version   = str_replace('Version', '', $className);
            $result[(string)$version] = $filePath;
        }
 
        ksort($result, SORT_STRING);
        return $result;
    }

    /**
     * Returns list of already executed version strings.
     */
    private function getExecutedVersions(): array
    {
        $rows = $this->connector->fetchQuery(
            "SELECT version FROM " . self::MIGRATIONS_TABLE . " ORDER BY executed_at ASC"
        );
        return array_column($rows, 'version');
    }

    /**
     * Load and return a MigrationInterface instance from a file.
     */
    private function loadMigration(string $filePath): MigrationInterface
    {
        if (!file_exists($filePath)) {
            throw new KernelException("Migration file not found: {$filePath}");
        }
 
        require_once $filePath;
 
        $className = basename($filePath, '.php');
 
        if (!class_exists($className)) {
            throw new KernelException("Migration class {$className} not found in {$filePath}");
        }
 
        $instance = new $className();
 
        if (!$instance instanceof MigrationInterface) {
            throw new KernelException("{$className} must implement MigrationInterface");
        }
 
        return $instance;
    }

    private function markAsExecuted(string $version): void
    {
        $this->connector->executeQuery(
            "INSERT INTO " . self::MIGRATIONS_TABLE . " (version, executed_at) VALUES (:version, NOW())",
            ['version' => $version]
        );
    }

      /**
     * Returns the last executed migration version, or null.
     */
    private function getLastExecutedMigration(): ?string
    {
        $row = $this->connector->fetchQueryOnce(
            "SELECT version FROM " . self::MIGRATIONS_TABLE . " ORDER BY executed_at DESC LIMIT 1"
        );
        return $row ? $row['version'] : null;
    }

    /**
     * Remove a version from the migrations table (rollback).
     */
    private function markAsRolledBack(string $version): void
    {
        $this->connector->executeQuery(
            "DELETE FROM " . self::MIGRATIONS_TABLE . " WHERE version = :version",
            ['version' => $version]
        );
    }
}

<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils\Migration;

use PDO;
use App\Kernel\Exceptions\KernelException;
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Connector\Interfaces\MigrationGeneratorInterface;
use App\Kernel\Connector\Utils\Migration\PostgreSqlMigrationGenerator;


class MigrationGenerator
{
    private MigrationGeneratorInterface $driver;

    public function __construct(ConnectorInterface $connector)
    {
        $driverName = $connector->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
 
        $this->driver = match($driverName) {
            'mysql'  => new MysqlMigrationGenerator(),
            'pgsql'  => new PostgreSqlMigrationGenerator(),
            'sqlite' => new SQLiteMigrationGenerator(),
            default  => throw new KernelException("Unsupported database driver: {$driverName}")
        };
    }

    /**
     * Generate SQL statements from a SchemaComparator diff.
     *
     * Returns:
     * [
     *   'safe'        => [...],  // CREATE TABLE, ADD COLUMN, ALTER COLUMN
     *   'destructive' => [...],  // DROP TABLE, DROP COLUMN
     * ]
     */
    public function generate(array $diff): array
    {
        return $this->driver->generate($diff);
    }
}

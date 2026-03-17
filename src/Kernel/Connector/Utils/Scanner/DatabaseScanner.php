<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils\Scanner;

use PDO;
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Connector\Interfaces\DatabaseScannerDriverInterface;
use App\Kernel\Exceptions\KernelException;

class DatabaseScanner
{

    private DatabaseScannerDriverInterface $driver;

    public function __construct(ConnectorInterface $connector)
    {
        /**@var \PDO $pdo */
        $pdo = $connector->getConnection();
        $driverName = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->driver = match ($driverName) {
            'mysql' => new MySQLScannerDriver($connector),
            'pgsql' => new PostGreScannerDriver($connector),
            'sqlite' => new SQLiteScannerDriver($connector),
            default => throw new KernelException("Unsupported driver: $driverName")
        };
    }

    public function scan(): array
    {
        return $this->driver->scan();
    }

    public function getDriver(): DatabaseScannerDriverInterface
    {
        return $this->driver;
    }
}

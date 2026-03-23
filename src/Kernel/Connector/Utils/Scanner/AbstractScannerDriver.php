<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils\Scanner;

use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Connector\Interfaces\DatabaseScannerDriverInterface;

abstract class AbstractScannerDriver implements DatabaseScannerDriverInterface
{
    protected string $schemaName;
    protected array $excludedTables = [];

    public function __construct(protected ConnectorInterface $connector)
    {
        $this->schemaName = $this->connector->fetchQueryOnce('select DATABASE() as db')['db'];
    }

    public function excludeTables(array $tables): void
    {
        $this->excludedTables = $tables;
    }

    public function tableExists(string $table): bool
    {
        $rows = $this->connector->fetchQuery(
            "SELECT COUNT(*) as cnt FROM information_schema.tables 
         WHERE table_schema = :schema AND table_name = :table",
            ['schema' => $this->schemaName, 'table' => $table]
        );
        return (int) $rows[0]['cnt'] > 0;
    }
}

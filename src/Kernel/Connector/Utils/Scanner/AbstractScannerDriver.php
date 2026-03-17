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
    public function __construct(protected ConnectorInterface $connector)
    {
        $this->schemaName = $this->connector->fetchQueryOnce('select DATABASE() as db')['db'];
    }
}

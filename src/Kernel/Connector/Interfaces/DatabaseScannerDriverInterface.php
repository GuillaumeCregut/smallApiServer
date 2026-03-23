<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Interfaces;

interface DatabaseScannerDriverInterface
{
    public function getTables(): array;
    public function getColumns(string $table): array;
    public function getPrimaryKeys(string $table): array;
    public function getForeignKeys(string $table): array;
    public function getIndexes(string $table): array;
    public function tableExists(string $table): bool;
    public function excludeTables(array $tables): void;
    public function scan(): array;
}
<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils\Migration;

class PostgreSqlMigrationGenerator extends AbstractMigrationGeneratorDriver
{
    protected function wrapIdentifier(string $name): string
    {
        return "\"{$name}\"";
    }
 
    protected function autoIncrementKeyword(): string
    {
        // SERIAL handles both type and auto-increment in PostgreSQL
        // so we override buildColumnDefinition for the id case via toSQLType
        return '';
    }
 
    /**
     * For PostgreSQL, 'id' auto-increment uses SERIAL — no extra keyword needed.
     */
    protected function buildColumnDefinition(string $colName, array $colDef): string
    {
        $type = $colDef['type'] ?? 'string';
        if ($type === 'relation') {
            $type = 'int';
        }
 
        if ($colName === 'id' && $type === 'int') {
            $c = $this->wrapIdentifier($colName);
            return "{$c} SERIAL NOT NULL";
        }
 
        return parent::buildColumnDefinition($colName, $colDef);
    }
 
    /**
     * PostgreSQL uses ALTER COLUMN ... TYPE and ALTER COLUMN ... SET/DROP NOT NULL
     * as separate statements.
     */
    protected function generateAlterColumn(string $table, string $colName, array $changes): string
    {
        $t   = $this->wrapIdentifier($table);
        $c   = $this->wrapIdentifier($colName);
        $sql = [];
 
        if (isset($changes['type'])) {
            $newType = $this->toSQLType($colName, $changes['type']['to']);
            $sql[]   = "ALTER TABLE {$t} ALTER COLUMN {$c} TYPE {$newType} USING {$c}::{$newType};";
        }
 
        if (isset($changes['nullable'])) {
            $sql[] = $changes['nullable']['to']
                ? "ALTER TABLE {$t} ALTER COLUMN {$c} DROP NOT NULL;"
                : "ALTER TABLE {$t} ALTER COLUMN {$c} SET NOT NULL;";
        }
 
        return implode("\n", $sql);
    }
 
    protected function toSQLType(string $colName, string $genericType): string
    {
        return match($genericType) {
            'int'      => 'INTEGER',
            'string'   => 'VARCHAR(255)',
            'bool'     => 'BOOLEAN',
            'float'    => 'NUMERIC(10,2)',
            'datetime' => 'TIMESTAMP',
            'date'     => 'DATE',
            'time'     => 'TIME',
            'json'     => 'JSONB',  // prefer jsonb over json in PostgreSQL
            default    => 'VARCHAR(255)',
        };
    }
}